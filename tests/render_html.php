<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$httpKernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$request = \Illuminate\Http\Request::create('/', 'GET');
$session = $app['session']->driver();
$session->start();
$request->setLaravelSession($session);

$response = $httpKernel->handle($request);
echo $response->getContent();
