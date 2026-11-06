<?php
include("config.php");

$debug = true;

function normalizeString($str) {
    $str = strtolower($str);
    return preg_replace('/[^\w\s]/', '', $str);
}

// Cache settings
$cacheFolder = __DIR__ . '/cache/';
$cacheFile = $cacheFolder . "songs_cache.sdb";
$cacheTime = 300; 

$stale = false;
if (file_exists($cacheFile)) {
    if ((time() - filemtime($cacheFile)) < $cacheTime) {
        $lines = file($cacheFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($debug) {
            error_log("Loaded library from cache (fresh)");
        }
    } else {
        $lines = file($cacheFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $stale = true;
        if ($debug) {
            error_log("Loaded library from cache (stale)");
        }
    }
} else {
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
        // Save the result to the cache file
        file_put_contents($cacheFile, implode("\n", $lines));
        if ($debug) {
            error_log("Saved library to cache (initial load)");
        }
    } else {
        echo "$errno: $errstr";
        exit;
    }
}

// This code is related to the caching for faster speed during showing results even after the exception
if ($stale) {
    header("X-Cache-Stale: true");
}

// Process the search query
$searchtext = isset($_POST['searchtext']) ? $_POST['searchtext'] : '';
$searchtext = stripslashes($searchtext);
$normSearch = normalizeString($searchtext);

$html = '<table class="table table-striped" style="border:1px solid #ccc; padding:5px; width:100%; background: #eee;">';

if (count($lines) > 0) {
    foreach ($lines as $entry) {
        if (empty($entry)) continue;
        list($artist, $title, $filename) = explode("|", $entry);
        $filename = trim($filename);
        $escapedFilename = str_replace('\\', '||', $filename);
        $combined = $title . " " . $artist;
        $norm = normalizeString($combined);
        
        if ($debug) {
            $html .= "\n<!-- Debug: combined='$combined', norm='$norm' -->\n";
        }
        
        if (!empty($normSearch) && strpos($norm, $normSearch) === false) {
            continue;
        }
        
        $html .= '<tr>
                    <td data-text="' . htmlspecialchars($title . " - " . $artist, ENT_QUOTES) . '" 
                        data-norm="' . htmlspecialchars($norm, ENT_QUOTES) . '" 
                        data-file="' . htmlspecialchars($escapedFilename, ENT_QUOTES) . '">
                      <b>' . htmlspecialchars($title, ENT_QUOTES) . '</b> <span style="font-style: italic;">' . htmlspecialchars($artist, ENT_QUOTES) . '</span>
                    </td>
                  </tr>';
    }
} else {
    $html .= '<tr><td data-text="no" data-file="no">no results</td></tr>';
}
$html .= "</table>";

echo $html;
?>
