<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Setting;

echo "--- TELEGRAM SETTINGS ---\n";
echo "Token: " . (Setting::getValue('telegram_bot_token') ? 'PRESENT' : 'MISSING') . "\n";
echo "Chat ID: " . (Setting::getValue('telegram_notify_group_id') ? 'PRESENT' : 'MISSING') . "\n";
echo "Chat ID Value: " . Setting::getValue('telegram_notify_group_id') . "\n";
