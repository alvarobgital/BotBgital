<?php

namespace App\Services;

use App\Models\NotificationsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class TelegramService
{
    /**
     * Send notification to the Bgital team Telegram group.
     */
    public function notifyTeam(string $message, ?int $conversationId = null): bool
    {
        $botToken = Setting::getValue('telegram_bot_token');
        $chatId = Setting::getValue('telegram_notify_group_id');

        if (empty($botToken) || empty($chatId)) {
            Log::warning('Telegram not configured — skipping notification');
            $this->logNotification('telegram', $chatId ?? 'not_configured', 'Team Notification', $message, $conversationId, 'failed');
            return false;
        }

        try {
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

            $response = Http::post($url, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            $status = $response->successful() ? 'sent' : 'failed';
            $this->logNotification('telegram', $chatId, 'Team Notification', $message, $conversationId, $status);

            return $response->successful();
        }
        catch (\Exception $e) {
            Log::error('Telegram notification failed: ' . $e->getMessage());
            $this->logNotification('telegram', $chatId, 'Team Notification', $message, $conversationId, 'failed');
            return false;
        }
    }

    protected function logNotification(string $type, string $recipient, string $subject, string $body, ?int $conversationId, string $status): void
    {
        NotificationsLog::create([
            'type' => $type,
            'recipient' => $recipient,
            'subject' => $subject,
            'body' => $body,
            'conversation_id' => $conversationId,
            'sent_at' => now(),
            'status' => $status,
        ]);
    }
}
