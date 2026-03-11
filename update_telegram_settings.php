<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Setting;

$settings = [
    'telegram_bot_token' => '8695075480:AAEXWJ-MkGhMaz6lLRf2KxveznZFfSVg3w8',
    'telegram_notify_group_id' => '-1003720862916',
];

foreach ($settings as $key => $value) {
    Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    echo "Updated setting: $key\n";
}
