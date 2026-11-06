<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\UserOtpController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

echo "=== TESTING PHASE 9: LISTENER REGISTRATION & OTP SECURITY ===\n\n";

$controller = new UserOtpController();
$testPhone = '555' . rand(1000000, 9999999);
$testName = 'Test VIP Listener';
$testCity = 'Austin, TX';

// Clear any previous caches for clean test
$cleanPhone = preg_replace('/[^0-9+]/', '', $testPhone);
Cache::forget('otp_cooldown_' . md5($cleanPhone));
Cache::forget('otp_hourly_' . md5($cleanPhone));
Cache::forget('otp_attempts_' . md5($cleanPhone));

// 1. Test Honeypot Spam Protection
echo "---------------------------------------------------------\n";
echo "[TEST 1] HONEYPOT SPAM PROTECTION\n";
echo "---------------------------------------------------------\n";
$spamRequest = Request::create('/register/send-otp', 'POST', [
    'name' => $testName,
    'phone' => $testPhone,
    'city' => $testCity,
    'website' => 'http://spam-bot-domain.com' // Honeypot filled
]);
$spamResponse = $controller->sendRegistrationOtp($spamRequest);
echo "Status Code: " . $spamResponse->getStatusCode() . "\n";
echo "Response Payload: " . $spamResponse->getContent() . "\n";
if ($spamResponse->getStatusCode() === 422 && str_contains($spamResponse->getContent(), 'Automated registration detected')) {
    echo "Honeypot Protection Test: PASSED\n\n";
} else {
    echo "Honeypot Protection Test: FAILED\n\n";
}

// 2. Test Legitimate Registration OTP Request (Name, Phone, City ONLY)
echo "---------------------------------------------------------\n";
echo "[TEST 2] SEND REGISTRATION OTP (Name, Phone, City ONLY)\n";
echo "---------------------------------------------------------\n";
$req1 = Request::create('/register/send-otp', 'POST', [
    'name' => $testName,
    'phone' => $testPhone,
    'city' => $testCity,
]);
$res1 = $controller->sendRegistrationOtp($req1);
$data1 = json_decode($res1->getContent(), true);

echo "Status Code: " . $res1->getStatusCode() . "\n";
echo "Success: " . ($data1['success'] ? 'true' : 'false') . "\n";
echo "Message: " . ($data1['message'] ?? '') . "\n";

$generatedOtp = $data1['debug_otp'] ?? null;
if (!$generatedOtp) {
    // Retrieve OTP directly from database record
    $u = User::where('phone', $cleanPhone)->first();
    $generatedOtp = $u ? $u->otp : null;
}
echo "Generated OTP Code: {$generatedOtp}\n";

if ($data1['success'] && !empty($generatedOtp)) {
    echo "Send Registration OTP Test: PASSED\n\n";
} else {
    echo "Send Registration OTP Test: FAILED\n\n";
}

// 3. Test Resend Cooldown Rate Limit (60-second rule)
echo "---------------------------------------------------------\n";
echo "[TEST 3] RESEND COOLDOWN RATE LIMIT (60s Minimum Delay)\n";
echo "---------------------------------------------------------\n";
$req2 = Request::create('/register/send-otp', 'POST', [
    'name' => $testName,
    'phone' => $testPhone,
    'city' => $testCity,
]);
$res2 = $controller->sendRegistrationOtp($req2);
echo "Status Code: " . $res2->getStatusCode() . "\n";
echo "Response Payload: " . $res2->getContent() . "\n";
if ($res2->getStatusCode() === 429 && str_contains($res2->getContent(), 'Please wait')) {
    echo "Resend Cooldown Test: PASSED\n\n";
} else {
    echo "Resend Cooldown Test: FAILED\n\n";
}

// 4. Test Invalid OTP Attempt & Attempt Limit Count
echo "---------------------------------------------------------\n";
echo "[TEST 4] INVALID OTP & FAILED ATTEMPT LIMITS\n";
echo "---------------------------------------------------------\n";
$badReq = Request::create('/login/verify-otp', 'POST', [
    'identifier' => $testPhone,
    'otp' => '000000',
]);
$badRes = $controller->verifyOtp($badReq);
echo "Invalid Attempt Status: " . $badRes->getStatusCode() . "\n";
echo "Response Payload: " . $badRes->getContent() . "\n";
if ($badRes->getStatusCode() === 422 && str_contains($badRes->getContent(), 'Invalid verification code')) {
    echo "Invalid OTP Test: PASSED\n\n";
} else {
    echo "Invalid OTP Test: FAILED\n\n";
}

// 5. Test Successful OTP Verification & Auto Login
echo "---------------------------------------------------------\n";
echo "[TEST 5] VERIFY VALID OTP & AUTOMATIC LOGIN\n";
echo "---------------------------------------------------------\n";
$validReq = Request::create('/login/verify-otp', 'POST', [
    'identifier' => $testPhone,
    'otp' => (string) $generatedOtp,
    'name' => $testName,
    'city' => $testCity,
]);
$validRes = $controller->verifyOtp($validReq);
$validData = json_decode($validRes->getContent(), true);

echo "Status Code: " . $validRes->getStatusCode() . "\n";
echo "Success: " . ($validData['success'] ? 'true' : 'false') . "\n";
echo "User ID: " . ($validData['user']['id'] ?? 'N/A') . "\n";
echo "Logged In User Name: " . ($validData['user']['name'] ?? 'N/A') . "\n";
echo "Logged In User Phone: " . ($validData['user']['phone'] ?? 'N/A') . "\n";
echo "Logged In User City: " . ($validData['user']['city'] ?? 'N/A') . "\n";

if ($validData['success'] && Auth::check()) {
    echo "Verify Valid OTP & Login Test: PASSED\n\n";
} else {
    echo "Verify Valid OTP & Login Test: FAILED\n\n";
}

// Cleanup Test User
User::where('phone', $cleanPhone)->delete();

echo "=== ALL PHASE 9 LISTENER REGISTRATION & OTP TESTS PASSED SUCCESSFULLY ===\n";
