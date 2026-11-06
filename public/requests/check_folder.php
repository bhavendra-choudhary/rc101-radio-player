<?php
$cacheFolder = __DIR__ . '/cache/';
if (is_writable($cacheFolder)) {
    echo "The cache folder is writable.";
} else {
    echo "The cache folder is not writable. Please check its permissions.";
}
?>
