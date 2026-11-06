<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class DeviceTokenAuthMiddleware
{
    /**
     * Handle an incoming request.
     * Automatically log in returning listeners using the secure, long-lived device token cookie.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            // If admin has disabled/deactivated user account, terminate session immediately
            if (isset($user->is_active) && !$user->is_active) {
                Auth::logout();
                if ($request->hasSession()) {
                    $request->session()->invalidate();
                }
                Cookie::queue(Cookie::forget('radio_device_token'));

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your account has been deactivated by the administrator.'
                    ], 403);
                }
                return redirect()->route('home')->with('error', 'Your account has been deactivated.');
            }
            return $next($request);
        }

        // Handle returning listener with secure long-lived device token cookie
        $rawToken = $request->cookie('radio_device_token');

        if ($rawToken && is_string($rawToken) && strlen($rawToken) >= 32) {
            $hashedToken = hash('sha256', $rawToken);
            
            // Validate server-side token storage & user status
            $user = User::where('device_token', $hashedToken)->first();

            if ($user) {
                // If user is deactivated or disabled by admin -> revoke token & reject auth
                if (isset($user->is_active) && !$user->is_active) {
                    Cookie::queue(Cookie::forget('radio_device_token'));
                } else {
                    // Valid active user -> automatic authentication
                    Auth::login($user, true);
                    if ($request->hasSession()) {
                        $request->session()->regenerate();
                    }
                }
            } else {
                // Token invalid or user deleted -> clear invalid cookie
                Cookie::queue(Cookie::forget('radio_device_token'));
            }
        }

        return $next($request);
    }
}
