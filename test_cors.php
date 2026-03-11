<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "--- Session & CORS Diagnostics ---\n";
echo "SESSION_DOMAIN: " . env('SESSION_DOMAIN') . "\n";
echo "SESSION_SECURE_COOKIE: " . env('SESSION_SECURE_COOKIE') . "\n";
echo "SANCTUM_STATEFUL_DOMAINS: " . env('SANCTUM_STATEFUL_DOMAINS') . "\n";
echo "APP_URL: " . env('APP_URL') . "\n";

// Check if Hostinger is acting as a reverse proxy requiring trusted proxies configuration
echo "\n--- Checking Proxy / SSL Status ---\n";
$request = \Illuminate\Http\Request::create(env('APP_URL') . '/api/auth/user', 'GET');
echo "Is Secure: " . ($request->isSecure() ? 'YES' : 'NO') . "\n";
echo "Host: " . $request->getHost() . "\n";
echo "Scheme: " . $request->getScheme() . "\n";

// Laravel 11 Trusted Proxies check:
$trustProxies = \Illuminate\Support\Facades\Config::get('app.trusted_proxies');
echo "Trusted Proxies: " . json_encode($trustProxies) . "\n";
