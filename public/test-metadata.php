<?php
// Simple test script to check stream metadata
$streamUrl = 'https://streams.radiomast.io/rc101';

echo "<h1>Stream Metadata Test</h1>";
echo "<p>Testing stream URL: " . htmlspecialchars($streamUrl) . "</p>";

// Test different endpoints
$endpoints = ['/status-json.xsl', '/currentsong', '/7.html', '/status.xsl'];

foreach ($endpoints as $endpoint) {
    $url = rtrim($streamUrl, '/') . $endpoint;
    echo "<h3>Testing endpoint: " . htmlspecialchars($endpoint) . "</h3>";
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'JamminRadio/1.0');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        
        curl_close($ch);
        
        echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
        echo "<p><strong>Content-Type:</strong> " . htmlspecialchars($contentType) . "</p>";
        echo "<p><strong>Response:</strong></p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        
        // Try to parse JSON
        if ($contentType && strpos($contentType, 'application/json') !== false) {
            $data = json_decode($response, true);
            if ($data) {
                echo "<p><strong>Parsed JSON:</strong></p>";
                echo "<pre>" . htmlspecialchars(print_r($data, true)) . "</pre>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<hr>";
}
?>
