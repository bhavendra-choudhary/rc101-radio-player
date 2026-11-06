<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SiteSetting;

echo "=========================================================\n";
echo "   PHASE 14: SEO + SOCIAL SHARING VERIFICATION SUITE      \n";
echo "=========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTag($html, $tagDescription, $pattern) {
    global $passCount, $failCount;
    if (preg_match($pattern, $html, $matches)) {
        echo "[PASS] {$tagDescription}: " . trim($matches[0]) . "\n";
        $passCount++;
        return true;
    } else {
        echo "[FAIL] {$tagDescription} (Pattern: {$pattern})\n";
        $failCount++;
        return false;
    }
}

function getRenderedHtml() {
    $out = [];
    exec('php ' . __DIR__ . '/render_html.php', $out);
    return implode("\n", $out);
}

// ---------------------------------------------------------
// CONFIGURATION 1: STATION A (POP HITS 92)
// ---------------------------------------------------------
echo ">>> Setting Station A Configurations (Pop Station)...\n";
SiteSetting::set('app_name', 'Pop Hits 92');
SiteSetting::set('seo_title', 'Pop Hits 92 - Top Chart Pop Music Live');
SiteSetting::set('seo_description', 'Listen to Pop Hits 92 live online for the best pop music and charts 24/7.');
SiteSetting::set('seo_keywords', 'pop music, chart hits, live radio');
SiteSetting::set('canonical_url', 'https://pophits92.com/live');
SiteSetting::set('robots_indexing', 'index, follow');
SiteSetting::set('og_title', 'Pop Hits 92 Live Stream');
SiteSetting::set('og_description', 'Stream live pop hits on any device.');
SiteSetting::set('og_image', 'images/pop_og_banner.png');
SiteSetting::set('facebook_share_image', 'images/pop_fb_share.png');
SiteSetting::set('text_share_image', 'images/pop_text_share.png');
SiteSetting::set('twitter_card', 'summary_large_image');
SiteSetting::set('twitter_site', '@PopHits92');
SiteSetting::set('twitter_title', 'Pop Hits 92 on X');
SiteSetting::set('twitter_description', 'Follow Pop Hits 92 live stream on X!');
SiteSetting::set('twitter_image', 'images/pop_twitter_share.png');
SiteSetting::set('favicon_path', 'favicon.ico');

$htmlA = getRenderedHtml();

echo "\n--- VERIFYING STATION A SERVER-RENDERED HTML METADATA ---\n";
assertTag($htmlA, "Page Title", '/<title>Pop Hits 92 - Top Chart Pop Music Live<\/title>/i');
assertTag($htmlA, "Meta Description", '/<meta name="description" content="Listen to Pop Hits 92 live online for the best pop music and charts 24\/7\."/i');
assertTag($htmlA, "Meta Keywords", '/<meta name="keywords" content="pop music, chart hits, live radio"/i');
assertTag($htmlA, "Canonical URL", '/<link rel="canonical" href="https:\/\/pophits92\.com\/live"/i');
assertTag($htmlA, "Robots Indexing", '/<meta name="robots" content="index, follow"/i');
assertTag($htmlA, "Favicon Link", '/<link rel="(icon|shortcut icon)" href="[^"]*favicon\.ico"/i');
assertTag($htmlA, "Open Graph Title", '/<meta property="og:title"\s+content="Pop Hits 92 Live Stream"/i');
assertTag($htmlA, "Open Graph Description", '/<meta property="og:description"\s+content="Stream live pop hits on any device\."/i');
assertTag($htmlA, "Open Graph Image", '/<meta property="og:image"\s+content="[^"]*images\/pop_og_banner\.png"/i');
assertTag($htmlA, "Facebook Sharing Image", '/<meta property="fb:share_image"\s+content="[^"]*images\/pop_fb_share\.png"/i');
assertTag($htmlA, "Text-Message Sharing Image", '/<meta property="msapplication-TileImage"\s+content="[^"]*images\/pop_text_share\.png"/i');
assertTag($htmlA, "X/Twitter Card", '/<meta name="twitter:card"\s+content="summary_large_image"/i');
assertTag($htmlA, "X/Twitter Site", '/<meta name="twitter:site"\s+content="@PopHits92"/i');
assertTag($htmlA, "X/Twitter Title", '/<meta name="twitter:title"\s+content="Pop Hits 92 on X"/i');
assertTag($htmlA, "X/Twitter Description", '/<meta name="twitter:description"\s+content="Follow Pop Hits 92 live stream on X!"/i');
assertTag($htmlA, "X/Twitter Image", '/<meta name="twitter:image"\s+content="[^"]*images\/pop_twitter_share\.png"/i');

// ---------------------------------------------------------
// CONFIGURATION 2: STATION B (COUNTRY ROADS 101)
// ---------------------------------------------------------
echo "\n>>> Updating Settings to Station B (Country Roads 101)...\n";
SiteSetting::set('app_name', 'Country Roads 101');
SiteSetting::set('seo_title', 'Country Roads 101 - Non-Stop Country Hits');
SiteSetting::set('seo_description', 'Stream the greatest country music classics and top country hits 24/7.');
SiteSetting::set('seo_keywords', 'country music, country hits, live stream');
SiteSetting::set('canonical_url', 'https://countryroads101.fm');
SiteSetting::set('robots_indexing', 'noindex, nofollow');
SiteSetting::set('og_title', 'Country Roads 101 Official Station');
SiteSetting::set('og_description', 'Listen live to Country Roads 101.');
SiteSetting::set('og_image', 'images/country_og.png');
SiteSetting::set('facebook_share_image', 'images/country_fb.png');
SiteSetting::set('text_share_image', 'images/country_text.png');
SiteSetting::set('twitter_card', 'summary');
SiteSetting::set('twitter_site', '@CountryRoads101');
SiteSetting::set('twitter_title', 'Country Roads 101 Radio');
SiteSetting::set('twitter_description', 'Country hits streaming live now.');
SiteSetting::set('twitter_image', 'images/country_twitter.png');

$htmlB = getRenderedHtml();

echo "\n--- VERIFYING STATION B SERVER-RENDERED HTML METADATA ---\n";
assertTag($htmlB, "Page Title", '/<title>Country Roads 101 - Non-Stop Country Hits<\/title>/i');
assertTag($htmlB, "Meta Description", '/<meta name="description" content="Stream the greatest country music classics and top country hits 24\/7\."/i');
assertTag($htmlB, "Canonical URL", '/<link rel="canonical" href="https:\/\/countryroads101\.fm"/i');
assertTag($htmlB, "Robots Indexing", '/<meta name="robots" content="noindex, nofollow"/i');
assertTag($htmlB, "Open Graph Title", '/<meta property="og:title"\s+content="Country Roads 101 Official Station"/i');
assertTag($htmlB, "Open Graph Image", '/<meta property="og:image"\s+content="[^"]*images\/country_og\.png"/i');
assertTag($htmlB, "Facebook Sharing Image", '/<meta property="fb:share_image"\s+content="[^"]*images\/country_fb\.png"/i');
assertTag($htmlB, "Text-Message Sharing Image", '/<meta property="msapplication-TileImage"\s+content="[^"]*images\/country_text\.png"/i');
assertTag($htmlB, "X/Twitter Card", '/<meta name="twitter:card"\s+content="summary"/i');
assertTag($htmlB, "X/Twitter Site", '/<meta name="twitter:site"\s+content="@CountryRoads101"/i');

echo "\n=========================================================\n";
if ($failCount === 0) {
    echo "  ALL SEO & SOCIAL SHARING TESTS PASSED 100% ({$passCount}/{$passCount})\n";
} else {
    echo "  SOME TESTS FAILED! Passed: {$passCount}, Failed: {$failCount}\n";
}
echo "=========================================================\n";

exit($failCount > 0 ? 1 : 0);
