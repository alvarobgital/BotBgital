<?php
// Outbound WhatsApp Test
$token = 'EAAeq9wShvScBQ7lsix3KH1d86YvN7fyVgZA6RtNSlXtQy92i9DtQ2AGolS8kVwZAVJ4jiM8yKdsDNT33xywMesv0bhnbj0w5rri8H5Chgtb2P8LFtTYPj2KwZB38nMMWkGPVIFQpwLI3ZAvvOgmSt96YRFLEqiVdqHd6g5xQea0u0hM3jlmhYZAkUPD0IA3eK6QZDZD';
$phoneId = '996727620194492';
$to = '527227453989'; // User's number

$url = "https://graph.facebook.com/v20.0/{$phoneId}/messages";
$data = [
    'messaging_product' => 'whatsapp',
    'to' => $to,
    'type' => 'text',
    'text' => ['body' => '🤖 TEST SALIENTE: Si recibes esto, el envío funciona.']
];

echo "Sending to WhatsApp...\n";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
