<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class UserOtpController extends Controller
{
    /**
     * Redirect direct registration visits to homepage with modal trigger.
     */
    public function showRegisterForm()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return redirect()->route('home', ['auth' => 'register']);
    }

    /**
     * Redirect direct login visits to homepage with modal trigger.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return redirect()->route('home', ['auth' => 'login']);
    }

    /**
     * Clean phone number utility.
     */
    private function cleanPhone($phone)
    {
        return preg_replace('/[^0-9+]/', '', trim($phone));
    }

    /**
     * Helper to find a user by Email or Phone.
     */
    private function findUser($identifier)
    {
        $identifier = trim($identifier);
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

        if ($isEmail) {
            return User::where('email', strtolower($identifier))->first();
        }

        $cleanPhone = $this->cleanPhone($identifier);
        return User::where('phone', $identifier)
            ->orWhere('phone', $cleanPhone)
            ->first();
    }

    /**
     * Send OTP for Listener Registration (Name, Phone, City ONLY).
     */
    public function sendRegistrationOtp(Request $request)
    {
        // Spam & automated registration bot protection (honeypot check)
        if ($request->filled('website') || $request->filled('bot_check')) {
            Log::warning('Automated registration attempt blocked by honeypot.');
            return response()->json([
                'success' => false,
                'message' => 'Automated registration detected.'
            ], 422);
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'city' => 'required|string|max:100',
        ], [
            'name.required' => 'Please enter your name.',
            'phone.required' => 'Please enter a valid phone number.',
            'city.required' => 'Please enter or confirm your city.',
        ]);

        $name = trim($request->input('name'));
        $phone = trim($request->input('phone'));
        $city = trim($request->input('city'));
        $cleanPhone = $this->cleanPhone($phone);

        if (strlen($cleanPhone) < 7) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid phone number (at least 7 digits).'
            ], 422);
        }

        // 1. Resend Cooldown Limit (Minimum 60 seconds between resend requests)
        $cooldownKey = 'otp_cooldown_' . md5($cleanPhone);
        $cooldownUntil = (int) Cache::get($cooldownKey, 0);
        if ($cooldownUntil > time()) {
            $secondsRemaining = $cooldownUntil - time();
            return response()->json([
                'success' => false,
                'message' => "Please wait {$secondsRemaining} seconds before requesting another code."
            ], 429);
        }

        // 2. Hourly Rate Limit (Maximum 5 OTP requests per hour per phone number)
        $hourlyKey = 'otp_hourly_' . md5($cleanPhone);
        $hourlyCount = (int) Cache::get($hourlyKey, 0);
        if ($hourlyCount >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum verification code limit reached (5 per hour). Please try again later.'
            ], 429);
        }

        $user = User::where('phone', $phone)
            ->orWhere('phone', $cleanPhone)
            ->first();

        if ($user && isset($user->is_active) && !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This account has been deactivated by the administrator.'
            ], 403);
        }

        // Generate 6-digit OTP code (Expires in 10 minutes)
        $otpCode = (string) rand(100000, 999999);
        $expiresAt = now()->addMinutes(10);

        if (!$user) {
            $user = User::create([
                'name' => $name,
                'phone' => $cleanPhone,
                'city' => $city,
                'email' => 'listener_' . time() . '_' . Str::random(6) . '@radiomast.local',
                'password' => bcrypt(Str::random(24)),
                'otp' => $otpCode,
                'otp_expires_at' => $expiresAt,
                'is_active' => true,
            ]);
        } else {
            $user->update([
                'name' => $name,
                'city' => $city,
                'otp' => $otpCode,
                'otp_expires_at' => $expiresAt,
            ]);
        }

        // Record Rate Limit Counters
        Cache::put($cooldownKey, time() + 60, 60);
        Cache::put($hourlyKey, $hourlyCount + 1, 3600);

        // Reset failed attempt counter upon generating a new OTP code
        Cache::forget('otp_attempts_' . md5($cleanPhone));

        Log::info("Registration OTP generated for phone {$cleanPhone}: {$otpCode}");

        $response = [
            'success' => true,
            'message' => 'Verification code sent successfully!',
            'phone' => $phone,
            'debug_otp' => $otpCode,
        ];

        return response()->json($response);
    }

    /**
     * Send OTP to provided Phone / Identifier (General Login flow).
     */
    public function sendOtp(Request $request)
    {
        // Spam protection honeypot check
        if ($request->filled('website') || $request->filled('bot_check')) {
            return response()->json(['success' => false, 'message' => 'Request rejected.'], 422);
        }

        $request->validate([
            'identifier' => 'required|string|max:255',
        ]);

        $identifier = trim($request->input('identifier'));
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $cleanPhone = $this->cleanPhone($identifier);

        // 1. Resend Cooldown Limit (60 seconds)
        $cooldownKey = 'otp_cooldown_' . md5($identifier);
        $cooldownUntil = (int) Cache::get($cooldownKey, 0);
        if ($cooldownUntil > time()) {
            $secondsRemaining = $cooldownUntil - time();
            return response()->json([
                'success' => false,
                'message' => "Please wait {$secondsRemaining} seconds before requesting another code."
            ], 429);
        }

        // 2. Hourly Rate Limit (Max 5 per hour)
        $hourlyKey = 'otp_hourly_' . md5($identifier);
        $hourlyCount = (int) Cache::get($hourlyKey, 0);
        if ($hourlyCount >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum verification code limit reached (5 per hour). Please try again later.'
            ], 429);
        }

        $user = $this->findUser($identifier);

        if ($user && isset($user->is_active) && !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This account has been deactivated by the administrator.'
            ], 403);
        }

        $otpCode = (string) rand(100000, 999999);
        $expiresAt = now()->addMinutes(10);

        if (!$user) {
            $user = User::create([
                'name' => $isEmail ? explode('@', $identifier)[0] : 'Listener_' . Str::random(4),
                'email' => $isEmail ? strtolower($identifier) : 'user_' . time() . '@radiomast.local',
                'phone' => !$isEmail ? $cleanPhone : null,
                'password' => bcrypt(Str::random(24)),
                'otp' => $otpCode,
                'otp_expires_at' => $expiresAt,
                'is_active' => true,
            ]);
        } else {
            $user->update([
                'otp' => $otpCode,
                'otp_expires_at' => $expiresAt,
            ]);
        }

        Cache::put($cooldownKey, time() + 60, 60);
        Cache::put($hourlyKey, $hourlyCount + 1, 3600);
        Cache::forget('otp_attempts_' . md5($identifier));

        Log::info("Login OTP generated for {$identifier}: {$otpCode}");

        $response = [
            'success' => true,
            'message' => 'Verification code sent successfully!',
            'identifier' => $identifier,
            'debug_otp' => $otpCode,
        ];

        return response()->json($response);
    }

    /**
     * Verify OTP and complete automatic registration & device token login.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $identifier = trim($request->input('identifier'));
        $otp = trim($request->input('otp'));
        $attemptsKey = 'otp_attempts_' . md5($identifier);

        $user = $this->findUser($identifier);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User record not found. Please request a new verification code.'
            ], 404);
        }

        if (isset($user->is_active) && !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated by the administrator.'
            ], 403);
        }

        // Check if maximum failed attempts (5 attempts limit) has been reached
        $failedAttempts = (int) Cache::get($attemptsKey, 0);
        if ($failedAttempts >= 5) {
            // Invalidate current OTP for security
            $user->update(['otp' => null, 'otp_expires_at' => null]);
            Cache::forget($attemptsKey);

            return response()->json([
                'success' => false,
                'message' => 'Maximum failed verification attempts exceeded (5 attempts). For security, your code has expired. Please request a new code.'
            ], 422);
        }

        // Check OTP expiration
        if (empty($user->otp) || !$user->otp_expires_at || now()->greaterThan($user->otp_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code has expired. Please request a new one.'
            ], 422);
        }

        // Verify OTP code match
        if (trim((string) $user->otp) !== trim((string) $otp)) {
            $failedAttempts++;
            Cache::put($attemptsKey, $failedAttempts, 600);
            $remaining = 5 - $failedAttempts;

            if ($remaining <= 0) {
                $user->update(['otp' => null, 'otp_expires_at' => null]);
                Cache::forget($attemptsKey);
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum verification attempts exceeded. Please request a new code.'
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => "Invalid verification code. You have {$remaining} attempt" . ($remaining === 1 ? '' : 's') . " remaining."
            ], 422);
        }

        // OTP Verified successfully! Reset attempts counter & clear OTP code
        Cache::forget($attemptsKey);

        if ($request->filled('city')) {
            $user->city = trim($request->input('city'));
        }
        if ($request->filled('name')) {
            $user->name = trim($request->input('name'));
        }

        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        // Create secure 10-year device token for permanent login on this device
        $deviceToken = $user->createDeviceToken();
        Cookie::queue(cookie('radio_device_token', $deviceToken, 5256000, null, null, false, true, false, 'Lax'));

        Auth::login($user, true);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return response()->json([
            'success' => true,
            'message' => 'Registration completed! Welcome to ' . config('app.name', 'Radio'),
            'redirect_url' => route('home'),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'city' => $user->city,
            ],
        ]);
    }

    /**
     * Log out user and revoke device token cookie.
     */
    public function logout(Request $request)
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            if (method_exists($user, 'revokeDeviceToken')) {
                $user->revokeDeviceToken();
            }
        }

        Cookie::queue(Cookie::forget('radio_device_token'));
        Auth::logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->header('HX-Request')) {
            return response('', 200)->header('HX-Redirect', route('home'));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect_url' => route('home')
            ]);
        }

        return redirect()->route('home')->with('success', 'Logged out successfully!');
    }
}
