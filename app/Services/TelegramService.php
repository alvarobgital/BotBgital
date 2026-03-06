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
            Log::warning('TelegramService: Token or ChatID not configured');
            return false;
        }

        try {
            $url = "https://api.telegram.org/bot{$token}/sendMessage";
            $response = Http::post($url, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown'
            ]);

            return $response->successful();
        }
        catch (\Exception $e) {
            Log::error('TelegramService Error: ' . $e->getMessage());
            return false;
        }
    }

    public static function notifyNewLead($customerName, $phone, $planName)
    {
        $text = "🚀 *Nuevo Interés de Contratación*\n\n";
        $text .= "👤 *Nombre:* {$customerName}\n";
        $text .= "📱 *WhatsApp:* {$phone}\n";
        $text .= "📦 *Plan:* {$planName}\n\n";
        $text .= "📩 _El bot ha asistido al prospecto, por favor contactar para cierre._";

        return self::sendMessage($text);
    }

    public static function notifySupportRequired($customerName, $phone, $problem, $accountNumber = 'Desconocido')
    {
        $text = "🚨 *Soporte Requerido*\n\n";
        $text .= "👤 *Cliente:* {$customerName}\n";
        $text .= "🔢 *Cuenta:* {$accountNumber}\n";
        $text .= "📱 *WhatsApp:* {$phone}\n";
        $text .= "⚠️ *Problema:* {$problem}\n\n";
        $text .= "🔧 _El cliente no pudo solucionar el problema con el bot. Se requiere técnico._";

        return self::sendMessage($text);
    }

    public static function notifyNoCoverageLead($phone, $zipCode, $neighborhood)
    {
        $text = "📍 *Prospecto Fuera de Cobertura*\n\n";
        $text .= "📱 *WhatsApp:* {$phone}\n";
        $text .= "📮 *Zona:* CP {$zipCode}, {$neighborhood}\n\n";
        $text .= "🤝 _Interesado en negociar cobertura en su zona._";

        return self::sendMessage($text);
    }
}
