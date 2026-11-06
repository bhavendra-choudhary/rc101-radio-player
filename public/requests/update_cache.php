<?php
include("config.php");
$debug = false;

function normalizeString($str) {
    $str = strtolower($str);
    return preg_replace('/[^\w\s]/', '', $str);
}

$cacheFolder = __DIR__ . '/cache/';
$cacheFile = $cacheFolder . "songs_cache.sdb";
$command = "Search=*\r\n";
$lines = array();

$fp = @fsockopen($studiohost, $studioport, $errno, $errstr, 10);
if ($fp !== false) {
    fwrite($fp, $command);
    $buffer = trim(fgets($fp));
    while (!empty($buffer) && ($buffer != "Not Found")) {
        $lines[] = $buffer;
        $buffer = trim(fgets($fp));
    }
    fclose($fp);
    file_put_contents($cacheFile, implode("\n", $lines));
    if ($debug) {
        error_log("Cache updated via update_cache.php");
    }
    echo "Cache updated.";
} else {
    echo "$errno: $errstr";
}
?>