<?php
// Copyright 2010-2013 by Jeff Harris - Jeff@HookedOnThe.Net
// Last modified on 2013-02-26

// Function to build the library from search results.
function buildlibrary($path) {
    global $studiohost, $studioport;
    $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    

    $line = array();

    $command = "Search=*\r\n";
    $fp = @fsockopen("$studiohost", $studioport, $errno, $errstr, 10); 
    if ($fp !== false) {
        fwrite($fp, $command);
        $buffer = ltrim(fgets($fp));
        while (!empty($buffer) && ($buffer != "Not Found")) {
            $element = strtoupper(substr($buffer, 0, 1));
            if (strpos($alphabet, $element) === false) {
                $element = "0";
            }
            $line[$element] .= $buffer;
            $buffer = ltrim(fgets($fp));
        }
        fclose($fp);
    } else {
        return "$errno: $errstr\r\n";
    }
    
    if (is_array($line)) {
        if (!empty($path)) {
            if (!is_dir($path)) {
                if (mkdir($path)) {
                    $path .= "/";
                    $i = "<"."?php header (\"Location: /\"); ?".">";
                    file_put_contents($path."index.php", $i);
                } else {
                    return "$errno: $errstr. Build aborted. \r\n";
                }
            }
            $path .= "/";
        }
        file_put_contents($path."TS.sdb", time());
        foreach ($line as $f => $value) {
            $filename = $path.$f.".sdb";
            file_put_contents($filename, $line[$f]);
        }
    }
    
    return "Library built successfully.\r\n";
}

// Function to get the current requests.
function getrequests($studiohost, $studioport) {
    $command = "List requests\r\n";
    $line = array();
    
    $fp = @fsockopen("$studiohost", $studioport, $errno, $errstr, 10); 
    if ($fp !== false) {
        fwrite($fp, $command);
        $buffer = trim(fgets($fp));
        while (!empty($buffer) && ($buffer != "OK")) {
            $line[] = $buffer;
            $buffer = trim(fgets($fp));
        }
        fclose($fp);
    } else {
        return "ERROR $errno: $errstr\r\n";
    }
    
    return $line;
}

// Function to list the upcoming requests.
function listrequests($studiohost, $studioport) {
    $list = getrequests($studiohost, $studioport);
    $results_page = "";
    
    if (is_array($list)) {
        $results_page = "<br /><br /><h4>Upcoming Requested Songs</h4>" .
                        "<table>\r\n";
        // Uncomment header rows if needed.
        /*
        $results_page .= "<tr>\r\n" .
                          "<th>Request Time</th>\r\n" .
                          "<th>Title - Artist</th>\r\n" .
                          "</tr>\r\n";
        */
    
        for ($i = 0; $i < count($list); $i++) {
            list($timestamp, $artist, $title) = explode("|", $list[$i]);
            $results_page .= "<tr><!--<td>$timestamp</td>--><td><b>$title</b> - <i>$artist</i></td><!--<td></td>--></tr>\r\n";
        }
    
        $results_page .= "</table>\r\n";
    } else if (empty($list)) {
        // Optionally return a message if there are no pending requests.
        $results_page = "There are no pending requests. \r\n";
    } else {
        return $list;
    }
    
    return $results_page;
}

// Function to get a random list of artists.
function getrandomartists($howmany) {
    global $studiohost, $studioport;
    
    // Send search command
    $command = "Search=*\r\n";
    $line = array();
    
    $fp = @fsockopen("$studiohost", $studioport, $errno, $errstr, 10); // open connection
    if ($fp !== false) {
        fwrite($fp, $command);
        $buffer = trim(fgets($fp));
        while (!empty($buffer) && ($buffer != "Not Found")) {
            $line[] = $buffer;
            $buffer = trim(fgets($fp));
        }
        fclose($fp);
    } else {
        return "$errno: $errstr\r\n";
    }
    
    if (is_array($line)) {
        $artists = array();
        for ($n = 0; $n < $howmany; $n++) {
            do {
                $i = mt_rand(0, count($line) - 1);
                list($artist, $title, $filename) = explode("|", $line[$i]);
            } while (in_array($artist, $artists) || empty($artist));
            $artists[] = $artist;
        }
        return $artists;
    }
}

// Function to create links for random artists.
function getartistlinks($howmany) {
    global $script;
    
    $artists = getrandomartists($howmany);
    $link = array();
    
    if (is_array($artists)) {
        foreach ($artists as $artist) {
            $link[] = "<a href=\"".$script."?func=Search&searchtext=".rawurlencode($artist)."\">".$artist."</a>";
        }
    }
    
    return $link;
}

// Function to calculate a time difference.
function timediff($timestamp, $detailed = false, $n = 0) {
    $now = time();
    ($timestamp >= $now) ? $action = 'away' : $action = 'ago';
    $diff = ($action == 'away' ? $timestamp - $now : $now - $timestamp);
    
    $periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
    $lengths = array(1, 60, 3600, 86400, 604800, 2630880, 31570560, 315705600);
    
    $i = sizeof($lengths) - 1;
    $time = "";
    while ($i >= $n) {
        if ($diff > $lengths[$i - 1]) {
            $val = floor($diff / $lengths[$i - 1]);
            $time .= $val . " " . $periods[$i - 1] . ($val > 1 ? 's ' : ' ');
            $diff -= ($val * $lengths[$i - 1]);
            if (!$detailed) {
                $i = 0;
            }
        }
        $i--;
    }
    
    if ($time == "") {
        return "later";
    } else {
        return "in " . rtrim($time);
    }
}
?>
