<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(\Illuminate\Http\Request::create('/', 'GET'));
echo "STATUS:" . $response->getStatusCode() . "\n";
if ($response->getStatusCode() !== 200) {
    echo "EXCEPTION/ERROR:\n" . substr($response->getContent(), 0, 500) . "\n";
}
