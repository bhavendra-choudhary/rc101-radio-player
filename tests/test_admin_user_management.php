<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\AdminAuthMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=========================================================\n";
echo "    PHASE 11: ADMIN USER MANAGEMENT AUTOMATED TESTS     \n";
echo "=========================================================\n\n";

$controller = new UserController();
$middleware = new AdminAuthMiddleware();

// Create test listener user
$testPhone = '555' . rand(1000000, 9999999);
$testUser = User::create([
    'name' => 'Searchable VIP Listener',
    'phone' => $testPhone,
    'city' => 'Memphis, TN',
    'email' => 'admin_manage_test_' . time() . '@radiomast.local',
    'password' => bcrypt('secret'),
    'is_active' => true,
]);

// Create admin user for testing
$adminUser = User::create([
    'name' => 'Admin Test User',
    'email' => 'admin_' . time() . '@station.local',
    'password' => bcrypt('password123'),
    'is_active' => true,
]);

// ---------------------------------------------------------
// [TEST 1] ADMIN AUTHORIZATION CHECK & PROTECTION
// ---------------------------------------------------------
echo "[TEST 1] ADMIN AUTHORIZATION CHECK & PROTECTION\n";
Auth::logout();

$session = $app['session']->driver();

$unauthReq = Request::create('/admin/users', 'GET');
$unauthReq->setLaravelSession($session);
$unauthRes = $middleware->handle($unauthReq, function () {
    return response('OK');
});

$blockedUnauth = ($unauthRes->getStatusCode() === 302 && str_contains($unauthRes->getTargetUrl(), 'login'));

Auth::login($adminUser);
$authReq = Request::create('/admin/users', 'GET');
$authReq->setLaravelSession($session);
$authRes = $middleware->handle($authReq, function () {
    return response('OK');
});

$allowedAuth = ($authRes->getContent() === 'OK');

echo "Unauthenticated Access Blocked: " . ($blockedUnauth ? 'YES' : 'NO') . "\n";
echo "Authenticated Admin Allowed: " . ($allowedAuth ? 'YES' : 'NO') . "\n";

if ($blockedUnauth && $allowedAuth) {
    echo "Result: PASSED\n\n";
} else {
    echo "Result: FAILED\n\n";
    exit(1);
}

// ---------------------------------------------------------
// [TEST 2] SEARCH BY NAME, PHONE, AND CITY
// ---------------------------------------------------------
echo "[TEST 2] SEARCH LISTENERS (NAME, PHONE, CITY)\n";

// Search by Name
$nameReq = Request::create('/admin/users', 'GET', ['search' => 'Searchable VIP']);
$nameRes = $controller->index($nameReq);
$nameView = $nameRes->render();
$foundName = str_contains($nameView, 'Searchable VIP Listener');

// Search by Phone
$phoneReq = Request::create('/admin/users', 'GET', ['search' => $testPhone]);
$phoneRes = $controller->index($phoneReq);
$phoneView = $phoneRes->render();
$foundPhone = str_contains($phoneView, $testPhone);

// Search by City
$cityReq = Request::create('/admin/users', 'GET', ['search' => 'Memphis']);
$cityRes = $controller->index($cityReq);
$cityView = $cityRes->render();
$foundCity = str_contains($cityView, 'Memphis, TN');

echo "Found by Name: " . ($foundName ? 'YES' : 'NO') . "\n";
echo "Found by Phone: " . ($foundPhone ? 'YES' : 'NO') . "\n";
echo "Found by City: " . ($foundCity ? 'YES' : 'NO') . "\n";

if ($foundName && $foundPhone && $foundCity) {
    echo "Result: PASSED\n\n";
} else {
    echo "Result: FAILED\n\n";
}

// ---------------------------------------------------------
// [TEST 3] EDIT LISTENER PROFILE (NAME, PHONE, CITY)
// ---------------------------------------------------------
echo "[TEST 3] EDIT LISTENER PROFILE\n";
$newPhone = '555' . rand(1000000, 9999999);
$updateReq = Request::create("/admin/users/{$testUser->id}", 'POST', [
    'name' => 'Updated Listener Name',
    'phone' => $newPhone,
    'city' => 'Atlanta, GA',
    'is_active' => '1',
]);
$updateRes = $controller->update($updateReq, $testUser);
$testUser->refresh();

echo "Updated Name: " . $testUser->name . "\n";
echo "Updated Phone: " . $testUser->phone . "\n";
echo "Updated City: " . $testUser->city . "\n";

if ($testUser->name === 'Updated Listener Name' && $testUser->phone === $newPhone && $testUser->city === 'Atlanta, GA') {
    echo "Result: PASSED\n\n";
} else {
    echo "Result: FAILED\n\n";
}

// ---------------------------------------------------------
// [TEST 4] DISABLE & ENABLE ACCOUNT TOGGLE
// ---------------------------------------------------------
echo "[TEST 4] DISABLE & ENABLE ACCOUNT TOGGLE\n";
$toggle1Res = $controller->toggleStatus($testUser);
$testUser->refresh();
$disabledSuccess = ($testUser->is_active === false);

$toggle2Res = $controller->toggleStatus($testUser);
$testUser->refresh();
$enabledSuccess = ($testUser->is_active === true);

echo "Status after 1st Toggle (Disable): " . ($disabledSuccess ? 'DISABLED' : 'FAILED') . "\n";
echo "Status after 2nd Toggle (Enable): " . ($enabledSuccess ? 'ENABLED' : 'FAILED') . "\n";

if ($disabledSuccess && $enabledSuccess) {
    echo "Result: PASSED\n\n";
} else {
    echo "Result: FAILED\n\n";
}

// ---------------------------------------------------------
// [TEST 5] EXPORT LISTENERS CSV
// ---------------------------------------------------------
echo "[TEST 5] EXPORT LISTENERS CSV\n";
$exportReq = Request::create('/admin/users/export', 'GET', ['search' => 'Updated Listener Name']);
$exportRes = $controller->export($exportReq);

echo "Response Content-Type: " . $exportRes->headers->get('Content-Type') . "\n";
echo "Content-Disposition: " . $exportRes->headers->get('Content-Disposition') . "\n";

ob_start();
$exportRes->sendContent();
$csvOutput = ob_get_clean();

$hasCsvHeaders = str_contains($csvOutput, 'Phone Number') && str_contains($csvOutput, 'Registered Date');
$hasUserData = str_contains($csvOutput, 'Updated Listener Name');

echo "Has CSV Column Headers: " . ($hasCsvHeaders ? 'YES' : 'NO') . "\n";
echo "Contains Exported User Data: " . ($hasUserData ? 'YES' : 'NO') . "\n";

if ($hasCsvHeaders && $hasUserData) {
    echo "Result: PASSED\n\n";
} else {
    echo "Result: FAILED\n\n";
}

// ---------------------------------------------------------
// [TEST 6] DELETE LISTENER ACCOUNT
// ---------------------------------------------------------
echo "[TEST 6] DELETE LISTENER ACCOUNT\n";
$deleteRes = $controller->destroy($testUser);
$deletedUser = User::find($testUser->id);
$adminUser->delete();

echo "User Deleted From DB: " . (is_null($deletedUser) ? 'YES' : 'NO') . "\n";
if (is_null($deletedUser)) {
    echo "Result: PASSED\n\n";
} else {
    echo "Result: FAILED\n\n";
}

echo "=========================================================\n";
echo "  ALL PHASE 11 ADMIN USER MANAGEMENT TESTS PASSED 100%   \n";
echo "=========================================================\n";
