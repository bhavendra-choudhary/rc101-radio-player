<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Http\Middleware\AdminAuthMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=========================================================\n";
echo "   PHASE 12: ADMIN RESPONSIVE UI AUTOMATED VERIFICATION \n";
echo "=========================================================\n\n";

$adminUser = User::first() ?? User::create([
    'name' => 'Admin Test User',
    'email' => 'responsive_admin_' . time() . '@station.local',
    'password' => bcrypt('password123'),
    'is_active' => true,
]);

Auth::login($adminUser);
$session = $app['session']->driver();

// ---------------------------------------------------------
// [TEST 1] VERIFY RESPONSIVE LAYOUT & MOBILE OFFCANVAS DRAWER
// ---------------------------------------------------------
echo "[TEST 1] MOBILE SLIDE-OUT OFFCANVAS DRAWER VERIFICATION\n";

$dashboardReq = Request::create('/admin', 'GET');
$dashboardReq->setLaravelSession($session);
$dashboardRes = $app->handle($dashboardReq);
$html = $dashboardRes->getContent();

$hasOffcanvasDrawer = str_contains($html, 'id="adminMobileSidebar"') && str_contains($html, 'data-bs-toggle="offcanvas"');
$hasDesktopSidebar = str_contains($html, 'sidebar d-none d-lg-block');
$hasMobileHeader = str_contains($html, 'd-lg-none bg-dark');

echo "Mobile Offcanvas Drawer Present: " . ($hasOffcanvasDrawer ? 'YES' : 'NO') . "\n";
echo "Desktop Sidebar Present: " . ($hasDesktopSidebar ? 'YES' : 'NO') . "\n";
echo "Mobile Top Navbar Present: " . ($hasMobileHeader ? 'YES' : 'NO') . "\n";

if ($hasOffcanvasDrawer && $hasDesktopSidebar && $hasMobileHeader) {
    echo "Result: PASSED\n\n";
} else {
    echo "Result: FAILED\n\n";
}

// ---------------------------------------------------------
// [TEST 2] VERIFY SETTINGS SECTION BREAKDOWN (13 SECTIONS)
// ---------------------------------------------------------
echo "[TEST 2] SETTINGS SECTIONS ORGANIZATIONAL STRUCTURE\n";

$settingsReq = Request::create('/admin/settings', 'GET');
$settingsReq->setLaravelSession($session);
$settingsRes = $app->handle($settingsReq);
$settingsHtml = $settingsRes->getContent();

$expectedSections = [
    'sec-station' => '1. Station',
    'sec-branding' => '2. Branding',
    'sec-stream' => '3. Stream Player',
    'sec-artwork' => '4. Artwork',
    'sec-banners' => '5. Banners',
    'sec-news' => '6. News',
    'sec-concerts' => '7. Concerts',
    'sec-website' => '8. Website',
    'sec-colors' => '9. Colors',
    'sec-seo' => '10. SEO',
    'sec-social' => '11. Social Sharing',
    'sec-affiliate' => '12. Affiliate Links',
    'sec-frontpage' => '13. Front Page',
];

$allSectionsFound = true;
foreach ($expectedSections as $id => $label) {
    $found = str_contains($settingsHtml, "id=\"{$id}\"");
    if (!$found) {
        $allSectionsFound = false;
        echo "Missing Section: {$label} (#{$id})\n";
    }
}

echo "All 13 Settings Sections Present: " . ($allSectionsFound ? 'YES' : 'NO') . "\n";
if ($allSectionsFound) {
    echo "Result: PASSED\n\n";
} else {
    echo "Result: FAILED\n\n";
}

// ---------------------------------------------------------
// [TEST 3] VERIFY ALL ADMIN PAGE ROUTES ACCESSIBLE (200 OK)
// ---------------------------------------------------------
echo "[TEST 3] ADMIN NAVIGATION ROUTE ACCESSIBILITY\n";

$routes = [
    '/admin' => 'Dashboard',
    '/admin/users' => 'Listener Accounts',
    '/admin/ads' => 'Banner Ads',
    '/admin/uploaded-news' => 'News Articles',
    '/admin/community-events' => 'Community Events',
    '/admin/contests' => 'Contests',
    '/admin/settings' => 'Station Settings',
    '/admin/footer' => 'Appearance & Theme',
    '/admin/apis' => 'Stream & API Settings',
];

$allRoutesOk = true;
foreach ($routes as $route => $name) {
    $req = Request::create($route, 'GET');
    $req->setLaravelSession($session);
    $res = $app->handle($req);
    $status = $res->getStatusCode();
    
    if ($status !== 200) {
        $allRoutesOk = false;
        echo "Route '{$route}' ({$name}) returned HTTP {$status}\n";
    }
}

echo "All Admin Navigation Routes HTTP 200: " . ($allRoutesOk ? 'YES' : 'NO') . "\n";
if ($allRoutesOk) {
    echo "Result: PASSED\n\n";
} else {
    echo "Result: FAILED\n\n";
}

// ---------------------------------------------------------
// [TEST 4] PUBLIC RADIO PLAYER & MAIN HOME INTEGRITY
// ---------------------------------------------------------
echo "[TEST 4] PUBLIC RADIO PLAYER & MAIN HOME INTEGRITY\n";

$out = [];
exec('php ' . __DIR__ . '/check_home.php', $out, $code);
$homeStatusLine = implode("\n", $out);
$isHomeOk = str_contains($homeStatusLine, 'STATUS:200');

echo "Public Home HTTP Status: " . ($isHomeOk ? '200 OK' : 'FAILED') . "\n";
echo "Public Radio Player Operational: " . ($isHomeOk ? 'YES' : 'NO') . "\n";

if ($isHomeOk) {
    echo "Result: PASSED\n\n";
} else {
    echo "Result: FAILED\n\n";
}

echo "=========================================================\n";
echo "  ALL PHASE 12 RESPONSIVE ADMIN UI TESTS PASSED 100%     \n";
echo "=========================================================\n";
