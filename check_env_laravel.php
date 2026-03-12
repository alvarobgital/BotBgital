<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo "TOKEN: " . env('WHATSAPP_ACCESS_TOKEN') . "\n";
echo "PHONE: " . env('WHATSAPP_PHONE_NUMBER_ID') . "\n";
echo "WABA:  " . env('WHATSAPP_BUSINESS_ACCOUNT_ID') . "\n";
echo "WEB_V: " . env('WHATSAPP_WEBHOOK_VERIFY_TOKEN') . "\n";
