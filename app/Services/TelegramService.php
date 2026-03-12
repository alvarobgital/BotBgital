<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public static function sendMessage($message)
    {
        $token = Setting::getValue('telegram_bot_token');
        $chatId = Setting::getValue('telegram_notify_group_id');

        if (!$token || !$chatId) {
            file_put_contents(base_path('bot_debug.log'), "TelegramService: Token or ChatID not configured\n", FILE_APPEND);
            Log::warning('TelegramService: Token or ChatID not configured');
            return false;
        }

        try {
            file_put_contents(base_path('bot_debug.log'), date('[Y-m-d H:i:s]') . " TelegramService: Sending message to $chatId\n", FILE_APPEND);
            Log::info("TelegramService: Sending message", ['chat_id' => $chatId, 'text_preview' => mb_substr($message, 0, 50)]);
            $url = "https://api.telegram.org/bot{$token}/sendMessage";
            $response = Http::post($url, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);

            if (!$response->successful()) {
                $errLog = "TelegramService ERROR: status={$response->status()} body=" . $response->body() . "\n";
                file_put_contents(base_path('bot_debug.log'), $errLog, FILE_APPEND);
                Log::error('TelegramService: API request failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'url' => $url
                ]);
            } else {
                file_put_contents(base_path('bot_debug.log'), "TelegramService: Message sent successfully\n", FILE_APPEND);
            }

            return $response->successful();
        }
        catch (\Exception $e) {
            file_put_contents(base_path('bot_debug.log'), "TelegramService EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
            Log::error('TelegramService Error: ' . $e->getMessage());
            return false;
        }
    }

    public static function notifyNewProspect($data)
    {
        $phone = $data['phone'] ?? 'N/A';
        $zip = $data['zip'] ?? 'N/A';
        $colonia = $data['colonia'] ?? 'Confirmada en zona de cobertura';
        $category = $data['category'] ?? 'Hogar';
        $plan = $data['plan'] ?? 'No especificado';
        $price = $data['price'] ?? '';
        $summary = $data['summary'] ?? '';
        $name = $data['name'] ?? 'Desconocido';

        $text = "<b>🟢 NUEVO PROSPECTO — BGITAL Telecomunicaciones</b>\n";
        $text .= "━━━━━━━━━━━━━━━━━━\n";
        $text .= "👤 <b>Tipo:</b> Posible Cliente\n";
        $text .= "👤 <b>Nombre:</b> " . htmlspecialchars($name) . "\n";
        $text .= "📱 <b>Teléfono:</b> +" . $phone . "\n";
        $text .= "📍 <b>CP:</b> " . $zip . "\n";
        $text .= "🏘️ <b>Colonia:</b> " . htmlspecialchars($colonia) . "\n";
        $text .= "🏢 <b>Tipo:</b> " . htmlspecialchars($category) . "\n";
        $text .= "📦 <b>Plan de interés:</b> " . htmlspecialchars($plan) . "\n";
        if ($price) $text .= "💰 <b>Precio:</b> " . htmlspecialchars($price) . "\n";
        $text .= "\n💬 <b>Resumen:</b>\n" . htmlspecialchars($summary) . "\n";
        $text .= "━━━━━━━━━━━━━━━━━━\n";
        $text .= "📅 <b>Fecha y hora:</b> " . now()->setTimezone('America/Mexico_City')->format('d/m/Y — h:i a');

        return self::sendMessage($text);
    }

    public static function notifyTechnicalAlert($data)
    {
        $name = $data['name'] ?? 'Cliente';
        $phone = $data['phone'] ?? 'N/A';
        $account = $data['account'] ?? 'N/A';
        $plan = $data['plan'] ?? 'N/A';
        $status = $data['status'] ?? 'Activo';
        $reason = $data['reason'] ?? 'Problema técnico no resuelto';
        $summary = $data['summary'] ?? '';

        $text = "<b>🚨 ALERTA TÉCNICA — BGITAL Telecomunicaciones</b>\n";
        $text .= "━━━━━━━━━━━━━━━━━━\n";
        $text .= "👤 <b>Nombre:</b> " . htmlspecialchars($name) . "\n";
        $text .= "📱 <b>Teléfono:</b> +" . $phone . "\n";
        $text .= "🔢 <b>Cuenta:</b> " . htmlspecialchars($account) . "\n";
        $text .= "📦 <b>Plan:</b> " . htmlspecialchars($plan) . "\n";
        $text .= "✅ <b>Estado:</b> " . htmlspecialchars($status) . "\n";
        $text .= "🔴 <b>Motivo:</b> " . htmlspecialchars($reason) . "\n";
        $text .= "\n💬 <b>Resumen de la conversación:</b>\n" . htmlspecialchars($summary) . "\n";
        $text .= "━━━━━━━━━━━━━━━━━━\n";
        $text .= "📅 <b>Fecha y hora:</b> " . now()->setTimezone('America/Mexico_City')->format('d/m/Y — h:i a');

        return self::sendMessage($text);
    }

    public static function notifyProspectNoCoverage($data)
    {
        $phone = $data['phone'] ?? 'N/A';
        $zip = $data['zip'] ?? 'N/A';
        $category = $data['category'] ?? 'No especificado';
        $zonesAvailable = $data['zones_available'] ?? '';
        $coloniaInput = $data['colonia_input'] ?? 'NO aparece en zonas cubiertas';

        $text = "<b>🟡 PROSPECTO SIN COBERTURA — BGITAL Telecomunicaciones</b>\n";
        $text .= "━━━━━━━━━━━━━━━━━━\n";
        $text .= "👤 <b>Tipo:</b> Posible Cliente\n";
        $text .= "📱 <b>Teléfono:</b> +" . $phone . "\n";
        $text .= "📍 <b>CP:</b> " . $zip . "\n";
        $text .= "🏘️ <b>Colonia:</b> " . htmlspecialchars($coloniaInput) . "\n";
        if ($zonesAvailable) {
            $text .= "   (Zonas disponibles en ese CP: " . htmlspecialchars($zonesAvailable) . ")\n";
        }
        $text .= "🏢 <b>Tipo de servicio:</b> " . htmlspecialchars($category) . "\n";
        $text .= "\n💬 <b>Resumen:</b>\n";
        $text .= "— Verificó cobertura en CP " . $zip . "\n";
        $text .= "— Confirmó que su colonia NO tiene cobertura ❌\n";
        $text .= "— Interesado en contratar servicio " . htmlspecialchars($category) . "\n";
        $text .= "— Bot escaló a asesor humano automáticamente\n";
        $text .= "\n⚠️ <b>Acción requerida:</b>\n";
        $text .= "— Evaluar si se puede ampliar cobertura a su zona\n";
        $text .= "— O bien ofrecer alternativa / lista de espera\n";
        $text .= "━━━━━━━━━━━━━━━━━━\n";
        $text .= "📅 <b>Fecha y hora:</b> " . now()->setTimezone('America/Mexico_City')->format('d/m/Y — h:i a');

        return self::sendMessage($text);
    }
}
