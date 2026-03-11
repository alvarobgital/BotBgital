<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Update Telegram Settings
\App\Models\Setting::updateOrCreate(
['key' => 'telegram_bot_token'],
['value' => '8695075480:AAEXWJ-MkGhMaz6lLRf2KxveznZFfSVg3w8', 'type' => 'string', 'display_name' => 'Telegram Bot Token', 'group' => 'Integraciones']
);

\App\Models\Setting::updateOrCreate(
['key' => 'telegram_notify_group_id'],
['value' => '@botBgital', 'type' => 'string', 'display_name' => 'Telegram Chat ID', 'group' => 'Integraciones']
);

echo "Settings updated.\n";

// Test Message
$res = \App\Services\TelegramService::sendMessage("✅ *Bot BGITAL* conectado exitosamente.\n_Prueba de notificaciones cortas._");

if ($res) {
    echo "Test message sent to Telegram successfully.\n";
}
else {
    echo "Failed to send test message to Telegram. Ensure the bot is admin in @botBgital.\n";
    // Check API response directly
    $token = '8695075480:AAEXWJ-MkGhMaz6lLRf2KxveznZFfSVg3w8';
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $response = \Illuminate\Support\Facades\Http::post($url, [
        'chat_id' => '@botBgital',
        'text' => 'Prueba directa',
    ]);
    echo "API Response: " . $response->body() . "\n";
}
