<?php
$host = "174.104.97.253";
$port = 443; // or 8200

// Attempt to open a socket connection
$connection = fsockopen($host, $port, $errno, $errstr, 10);

if ($connection) {
    echo "Connection to $host on port $port successful.";
    fclose($connection);
} else {
    echo "Connection failed: $errstr ($errno)";
}
?>
