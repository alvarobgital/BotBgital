<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TelegramService;
use App\Models\Setting;

$token = '8695075480:AAEXWJ-MkGhMaz6lLRf2KxveznZFfSVg3w8';
$chatId = '-1003720862916';

echo "--- TEST TELEGRAM DIRECT ---" . PHP_EOL;
echo "Token from user: $token" . PHP_EOL;
echo "Chat ID from user: $chatId" . PHP_EOL;

// Update settings to be sure
Setting::updateOrCreate(['key' => 'telegram_bot_token'], ['value' => $token]);
Setting::updateOrCreate(['key' => 'telegram_notify_group_id'], ['value' => $chatId]);

echo "Settings updated in DB." . PHP_EOL;

$msg = "<b>🧪 PRUEBA DIRECTA DE CONECTIVIDAD</b>\n" .
       "Si puedes leer esto, el Bot está configurado correctamente.\n" .
       "Fecha: " . date('d/m/Y H:i:s');

$result = TelegramService::sendMessage($msg);

if ($result) {
    echo "SUCCESS: Message sent to Telegram." . PHP_EOL;
} else {
    echo "FAILED: Check bot_debug.log." . PHP_EOL;
}
