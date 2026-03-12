<?php
// Standalone Telegram Test - No Laravel needed
$token = '8695075480:AAEXWJ-MkGhMaz6lLRf2KxveznZFfSVg3w8';
$chatId = '-1003720862916';
$message = "🤖 *TEST DIAGNÓSTICO*\nSistema: BotBgital\nHora: " . date('Y-m-d H:i:s') . "\nEstado: Verificando conexión directa.";

$url = "https://api.telegram.org/bot{$token}/sendMessage";
$data = [
    'chat_id' => $chatId,
    'text' => $message,
    'parse_mode' => 'Markdown'
];

echo "Sending to Telegram...\n";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo "CURL Error: " . $err . "\n";
} else {
    echo "Response: " . $response . "\n";
}
