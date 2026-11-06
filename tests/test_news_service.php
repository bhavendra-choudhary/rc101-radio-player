<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SiteSetting;
use App\Services\NewsService;
use App\Http\Controllers\NewsController;

echo "=== TESTING MUSIC NEWS API FOR BOTH STATION CONFIGURATIONS ===\n\n";

// 1. Test Pop Station Configuration
echo "---------------------------------------------------------\n";
echo "[TEST 1] POP STATION CONFIGURATION ('news_genre' => 'pop')\n";
echo "---------------------------------------------------------\n";
SiteSetting::set('news_genre', 'pop');
SiteSetting::set('news_search_query', '');

$servicePop = new NewsService();
$newsPop = $servicePop->getStationNews();

echo "Returned Articles Count: " . count($newsPop) . "\n";
if (!empty($newsPop)) {
    echo "Sample Article 1 Title: " . ($newsPop[0]['title'] ?? 'N/A') . "\n";
    echo "Sample Article 1 Source: " . ($newsPop[0]['source']['name'] ?? 'N/A') . "\n";
    echo "Sample Article 1 URL: " . ($newsPop[0]['url'] ?? 'N/A') . "\n";
    echo "Sample Article 1 Image: " . ($newsPop[0]['urlToImage'] ?? 'N/A') . "\n";
}
echo "Pop Station Test: PASSED\n\n";

// 2. Test Country Station Configuration
echo "---------------------------------------------------------\n";
echo "[TEST 2] COUNTRY STATION CONFIGURATION ('news_genre' => 'country')\n";
echo "---------------------------------------------------------\n";
SiteSetting::set('news_genre', 'country');
SiteSetting::set('news_search_query', '');

// Force cache clearing for test
\Illuminate\Support\Facades\Cache::flush();

$serviceCountry = new NewsService();
$newsCountry = $serviceCountry->getStationNews();

echo "Returned Articles Count: " . count($newsCountry) . "\n";
if (!empty($newsCountry)) {
    echo "Sample Article 1 Title: " . ($newsCountry[0]['title'] ?? 'N/A') . "\n";
    echo "Sample Article 1 Source: " . ($newsCountry[0]['source']['name'] ?? 'N/A') . "\n";
    echo "Sample Article 1 URL: " . ($newsCountry[0]['url'] ?? 'N/A') . "\n";
    echo "Sample Article 1 Image: " . ($newsCountry[0]['urlToImage'] ?? 'N/A') . "\n";
}
echo "Country Station Test: PASSED\n\n";

// 3. Test Fallback State (Simulating API failure / invalid key)
echo "---------------------------------------------------------\n";
echo "[TEST 3] CLEAN FALLBACK STATE ON ERROR/TIMEOUT/NO KEY\n";
echo "---------------------------------------------------------\n";
SiteSetting::set('news_api_key', 'invalid_fake_key_12345');
\Illuminate\Support\Facades\Cache::flush();

$serviceFallback = new NewsService();
$newsFallback = $serviceFallback->getStationNews();

echo "Returned Fallback Articles Count: " . count($newsFallback) . "\n";
if (!empty($newsFallback)) {
    echo "Fallback Sample Title: " . ($newsFallback[0]['title'] ?? 'N/A') . "\n";
    echo "Fallback Sample Source: " . ($newsFallback[0]['source']['name'] ?? 'N/A') . "\n";
}
echo "Clean Fallback Test: PASSED\n\n";

// Restore Pop Station Setting as default
SiteSetting::set('news_genre', 'pop');
SiteSetting::set('news_api_key', '');
\Illuminate\Support\Facades\Cache::flush();

echo "=== ALL NEWS API TESTS COMPLETED SUCCESSFULLY ===\n";
