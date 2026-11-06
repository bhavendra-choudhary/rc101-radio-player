<?php
/**
 * Cache Clearing Script
 * Run this file to clear all Laravel caches that might contain old URLs
 */

echo "Clearing Laravel caches...\n";

// Clear application cache
if (function_exists('exec')) {
    echo "1. Clearing application cache...\n";
    exec('php artisan cache:clear 2>&1', $output);
    echo implode("\n", $output) . "\n";
    
    echo "2. Clearing configuration cache...\n";
    exec('php artisan config:clear 2>&1', $output);
    echo implode("\n", $output) . "\n";
    
    echo "3. Clearing route cache...\n";
    exec('php artisan route:clear 2>&1', $output);
    echo implode("\n", $output) . "\n";
    
    echo "4. Clearing view cache...\n";
    exec('php artisan view:clear 2>&1', $output);
    echo implode("\n", $output) . "\n";
    
    echo "5. Optimizing configuration...\n";
    exec('php artisan config:cache 2>&1', $output);
    echo implode("\n", $output) . "\n";
} else {
    echo "Cannot run artisan commands. Manual cache clearing required.\n";
}

// Manual cache clearing - delete cache files
echo "6. Manual cache file clearing...\n";

$cacheDirs = [
    'storage/framework/cache/data/',
    'storage/framework/views/',
    'bootstrap/cache/'
];

foreach ($cacheDirs as $dir) {
    if (is_dir($dir)) {
        $files = glob($dir . '*');
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== '.gitignore') {
                unlink($file);
                echo "Deleted: $file\n";
            }
        }
        echo "Cleared directory: $dir\n";
    }
}

echo "\nCache clearing completed!\n";
echo "Please refresh your browser and try again.\n";
?>
