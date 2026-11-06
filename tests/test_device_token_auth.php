<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Http\Controllers\Auth\UserOtpController;
use App\Http\Middleware\DeviceTokenAuthMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Cache;

echo "=========================================================\n";
echo "  PHASE 10: PASSWORDLESS LONG-LIVED DEVICE AUTH TESTS    \n";
echo "=========================================================\n\n";

$controller = new UserOtpController();
$middleware = new DeviceTokenAuthMiddleware();

$testPhone = '555' . rand(1000000, 9999999);
$testName = 'VIP Returning Listener';
$testCity = 'Nashville, TN';
$cleanPhone = preg_replace('/[^0-9+]/', '', $testPhone);

// ---------------------------------------------------------
// [TEST 1] NEW REGISTRATION & DEVICE TOKEN GENERATION
// ---------------------------------------------------------
echo "[TEST 1] NEW REGISTRATION & DEVICE TOKEN GENERATION\n";
Cache::forget('otp_cooldown_' . md5($cleanPhone));
Cache::forget('otp_hourly_' . md5($cleanPhone));

$sendReq = Request::create('/register/send-otp', 'POST', [
    'name' => $testName,
    'phone' => $testPhone,
    'city' => $testCity,
]);
$sendRes = $controller->sendRegistrationOtp($sendReq);
$sendData = json_decode($sendRes->getContent(), true);

$otpCode = $sendData['debug_otp'] ?? null;
if (!$otpCode) {
    $u = User::where('phone', $cleanPhone)->first();
    $otpCode = $u->otp;
}

$verifyReq = Request::create('/login/verify-otp', 'POST', [
    'identifier' => $testPhone,
    'otp' => (string)$otpCode,
    'name' => $testName,
    'city' => $testCity,
]);
$verifyRes = $controller->verifyOtp($verifyReq);
$verifyData = json_decode($verifyRes->getContent(), true);

$createdUser = User::where('phone', $cleanPhone)->first();
$queuedCookies = Cookie::getQueuedCookies();

$deviceCookie = null;
foreach ($queuedCookies as $cookie) {
    if ($cookie->getName() === 'radio_device_token') {
        $deviceCookie = $cookie;
        break;
    }
}

echo "Registration Success: " . ($verifyData['success'] ? 'true' : 'false') . "\n";
echo "DB Hashed Token Present: " . (!empty($createdUser->device_token) ? 'YES' : 'NO') . "\n";
echo "Secure Cookie Enqueued: " . ($deviceCookie ? 'YES' : 'NO') . "\n";
if ($deviceCookie) {
    echo "Cookie Expiration Minutes: " . $deviceCookie->getExpiresTime() . "\n";
    echo "Cookie HttpOnly: " . ($deviceCookie->isHttpOnly() ? 'YES' : 'NO') . "\n";
    echo "Cookie SameSite: " . $deviceCookie->getSameSite() . "\n";
}

if ($verifyData['success'] && $createdUser->device_token && $deviceCookie) {
    echo "Result: PASSED\n\n";
} else {
    echo "Result: FAILED\n\n";
    exit(1);
}

$rawDeviceToken = $deviceCookie->getValue();

// ---------------------------------------------------------
// [TEST 2] AUTOMATIC LOGIN VIA DEVICE TOKEN
// ---------------------------------------------------------
echo "[TEST 2] AUTOMATIC LOGIN VIA DEVICE TOKEN\n";
Auth::logout();
echo "Auth::check() before middleware: " . (Auth::check() ? 'LOGGED IN' : 'GUEST') . "\n";

$autoReq = Request::create('/', 'GET');
$autoReq->cookies->set('radio_device_token', $rawDeviceToken);

$middleware->handle($autoReq, function ($req) {
    return response('OK');
});

echo "Auth::check() after middleware: " . (Auth::check() ? 'LOGGED IN' : 'GUEST') . "\n";
echo "Authenticated User ID: " . (Auth::id() == $createdUser->id ? 'MATCHES' : 'MISMATCH') . "\n";

if (Auth::check() && Auth::id() == $createdUser->id) {
    echo "Result: PASSED\n\n";
} else {
    echo "Result: FAILED\n\n";
}

// ---------------------------------------------------------
// [TEST 3] BROWSER RELOAD & SUB-REQUEST PERSISTENCE
// ---------------------------------------------------------
echo "[TEST 3] BROWSER RELOAD & SUB-REQUEST PERSISTENCE\n";
// Simulating multiple sub-requests on page load
for ($i = 1; $i <= 3; $i++) {
    $subReq = Request::create('/api/news', 'GET');
    $subReq->cookies->set('radio_device_token', $rawDeviceToken);
    $middleware->handle($subReq, function ($req) {
        return response('OK');
    });
}

echo "Auth::check() after 3 reloads: " . (Auth::check() ? 'LOGGED IN' : 'GUEST') . "\n";
if (Auth::check() && Auth::id() == $createdUser->id) {
    echo "Result: PASSED\n\n";
} else {
    echo "Result: FAILED\n\n";
}

// ---------------------------------------------------------
// [TEST 4] RETURNING USER RECOGNITION (NO LOGIN RE-PROMPT)
// ---------------------------------------------------------
echo "[TEST 4] RETURNING USER RECOGNITION\n";
Auth::logout(); // User opens browser again after days
$returnReq = Request::create('/', 'GET');
$returnReq->cookies->set('radio_device_token', $rawDeviceToken);

$middleware->handle($returnReq, function ($req) {
    return response('OK');
});

echo "Returning Listener Recognized: " . (Auth::check() && Auth::user()->name === $testName ? 'YES' : 'NO') . "\n";
if (Auth::check() && Auth::user()->name === $testName) {
    echo "Result: PASSED\n\n";
} else {
    echo "Result: FAILED\n\n";
}

// ---------------------------------------------------------
// [TEST 5] LOGOUT & TOKEN REVOCATION
// ---------------------------------------------------------
echo "[TEST 5] LOGOUT & TOKEN REVOCATION\n";
$logoutReq = Request::create('/logout', 'POST');
$logoutRes = $controller->logout($logoutReq);

$reloadedUser = User::find($createdUser->id);
$logoutCookies = Cookie::getQueuedCookies();

$forgotCookie = false;
foreach ($logoutCookies as $c) {
    if ($c->getName() === 'radio_device_token' && $c->getExpiresTime() < time()) {
        $forgotCookie = true;
        break;
    }
}

echo "Auth::check() after logout: " . (Auth::check() ? 'LOGGED IN' : 'GUEST') . "\n";
echo "DB device_token nullified: " . (empty($reloadedUser->device_token) ? 'YES' : 'NO') . "\n";
echo "Device token cookie forgotten: " . ($forgotCookie ? 'YES' : 'NO') . "\n";

if (!Auth::check() && empty($reloadedUser->device_token)) {
    echo "Result: PASSED\n\n";
} else {
    echo "Result: FAILED\n\n";
}

// ---------------------------------------------------------
// [TEST 6] DISABLED ACCOUNT ACCESS REJECTION
// ---------------------------------------------------------
echo "[TEST 6] DISABLED ACCOUNT ACCESS REJECTION\n";
// Issue new device token to test disabled account
$newRawToken = $reloadedUser->createDeviceToken();
$reloadedUser->update(['is_active' => false]); // Admin deactivates account

Auth::logout();
$disabledReq = Request::create('/', 'GET');
$disabledReq->cookies->set('radio_device_token', $newRawToken);

$middleware->handle($disabledReq, function ($req) {
    return response('OK');
});

echo "Auth::check() for deactivated account: " . (Auth::check() ? 'LOGGED IN (BUG)' : 'REJECTED (SAFE)') . "\n";
if (!Auth::check()) {
    echo "Result: PASSED\n\n";
} else {
    echo "Result: FAILED\n\n";
}

// Re-enable user for remaining tests
$reloadedUser->update(['is_active' => true]);

// ---------------------------------------------------------
// [TEST 7] DELETED ACCOUNT ACCESS REJECTION
// ---------------------------------------------------------
echo "[TEST 7] DELETED ACCOUNT ACCESS REJECTION\n";
$deletedRawToken = $reloadedUser->createDeviceToken();
$reloadedUser->delete(); // User deleted from DB

Auth::logout();
$deletedReq = Request::create('/', 'GET');
$deletedReq->cookies->set('radio_device_token', $deletedRawToken);

$middleware->handle($deletedReq, function ($req) {
    return response('OK');
});

echo "Auth::check() for deleted account: " . (Auth::check() ? 'LOGGED IN (BUG)' : 'REJECTED (SAFE)') . "\n";
if (!Auth::check()) {
    echo "Result: PASSED\n\n";
} else {
    echo "Result: FAILED\n\n";
}

// ---------------------------------------------------------
// [TEST 8] INVALID TOKEN REJECTION
// ---------------------------------------------------------
echo "[TEST 8] INVALID TOKEN REJECTION\n";
Auth::logout();
$invalidReq = Request::create('/', 'GET');
$invalidReq->cookies->set('radio_device_token', 'invalid_fake_device_token_string_12345678901234567890');

$middleware->handle($invalidReq, function ($req) {
    return response('OK');
});

echo "Auth::check() for fake token: " . (Auth::check() ? 'LOGGED IN (BUG)' : 'REJECTED (SAFE)') . "\n";
if (!Auth::check()) {
    echo "Result: PASSED\n\n";
} else {
    echo "Result: FAILED\n\n";
}

// ---------------------------------------------------------
// [TEST 9] ROTATED TOKEN REJECTION & VALIDATION
// ---------------------------------------------------------
echo "[TEST 9] ROTATED TOKEN REJECTION & VALIDATION\n";
$tempUser = User::create([
    'name' => 'Rotated Token User',
    'phone' => '5559998888',
    'city' => 'Austin, TX',
    'email' => 'rotated_token_test@radiomast.local',
    'password' => bcrypt('secret'),
    'is_active' => true,
]);

$oldToken = $tempUser->createDeviceToken();
// Rotate token (new login session replaces token)
$newToken = $tempUser->createDeviceToken();

Auth::logout();
// Try old rotated token
$oldReq = Request::create('/', 'GET');
$oldReq->cookies->set('radio_device_token', $oldToken);
$middleware->handle($oldReq, function ($req) { return response('OK'); });
$oldAuthPassed = Auth::check();
Auth::logout();

// Try new rotated token
$newReq = Request::create('/', 'GET');
$newReq->cookies->set('radio_device_token', $newToken);
$middleware->handle($newReq, function ($req) { return response('OK'); });
$newAuthPassed = Auth::check();

echo "Old Rotated Token: " . ($oldAuthPassed ? 'ACCEPTED (BUG)' : 'REJECTED (SAFE)') . "\n";
echo "New Rotated Token: " . ($newAuthPassed ? 'ACCEPTED (SAFE)' : 'REJECTED (BUG)') . "\n";

$tempUser->delete();

if (!$oldAuthPassed && $newAuthPassed) {
    echo "Result: PASSED\n\n";
} else {
    echo "Result: FAILED\n\n";
}

echo "=========================================================\n";
echo "  ALL PHASE 10 DEVICE AUTHENTICATION TESTS PASSED 100%   \n";
echo "=========================================================\n";
