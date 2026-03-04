<?php

namespace App\Services;

use App\Models\NotificationsLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailNotificationService
{
    /**
     * Send handoff alert email to the support team.
     */
    public function sendHandoffAlert(array $data, ?int $conversationId = null): bool
    {
        $recipients = array_filter([
            config('mail.admin_address'),
            config('mail.support_address'),
        ]);

        if (empty($recipients)) {
            $recipients = ['admin@bgital.mx', 'soporte@bgital.mx'];
        }

        $subject = '🔔 Nuevo cliente esperando asesor — BotBgital';
        $body = $this->buildHandoffBody($data);

        try {
            Mail::raw($body, function ($message) use ($recipients, $subject) {
                $message->to($recipients)
                    ->subject($subject);
            });

            foreach ($recipients as $recipient) {
                $this->logNotification('email', $recipient, $subject, $body, $conversationId, 'sent');
            }

            return true;
        }
        catch (\Exception $e) {
            Log::error('Handoff email failed: ' . $e->getMessage());

            foreach ($recipients as $recipient) {
                $this->logNotification('email', $recipient, $subject, $body, $conversationId, 'failed');
            }

            return false;
        }
    }

    protected function buildHandoffBody(array $data): string
    {
        $name = $data['contact_name'] ?? 'Desconocido';
        $phone = $data['contact_phone'] ?? 'N/A';
        $lastMessage = $data['last_message'] ?? '';
        $panelUrl = $data['panel_url'] ?? '';

        return <<<EOT
🔔 Nuevo cliente esperando asesor

👤 Nombre: {$name}
📱 Teléfono: {$phone}
💬 Último mensaje: {$lastMessage}
🔗 Ver conversación: {$panelUrl}

Este es un correo automático del sistema BotBgital.
EOT;
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
