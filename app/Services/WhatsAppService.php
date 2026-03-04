<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $phoneNumberId;
    protected string $accessToken;
    protected string $apiUrl;

    public function __construct()
    {
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->accessToken = config('services.whatsapp.access_token');
        $this->apiUrl = "https://graph.facebook.com/v22.0/{$this->phoneNumberId}/messages";
    }

    public function sendText(string $to, string $message): ?array
    {
        return $this->sendRequest([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $message],
        ]);
    }

    public function sendInteractiveButtons(string $to, string $bodyText, array $buttonsKeyValue): ?array
    {
        $formattedButtons = collect($buttonsKeyValue)->take(3)->map(function ($title, $id) {
            return [
            'type' => 'reply',
            'reply' => [
            'id' => (string)$id,
            'title' => substr($title, 0, 20),
            ],
            ];
        })->values()->toArray();

        return $this->sendRequest([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $bodyText],
                'action' => ['buttons' => $formattedButtons],
            ],
        ]);
    }

    public function sendMedia(string $to, string $mediaUrl, string $mediaType, ?string $caption = null): ?array
    {
        // Types can be: image, video, audio, document
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => $mediaType,
            $mediaType => [
                'link' => $mediaUrl,
            ],
        ];

        if ($caption && in_array($mediaType, ['image', 'video', 'document'])) {
            $payload[$mediaType]['caption'] = $caption;
        }

        return $this->sendRequest($payload);
    }

    protected function sendRequest(array $payload): ?array
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->post($this->apiUrl, $payload);

            if ($response->successful()) {
                Log::info('WhatsApp message sent', ['to' => $payload['to'] ?? 'unknown']);
                return $response->json();
            }

            Log::error('WhatsApp API error', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return null;
        }
        catch (\Exception $e) {
            Log::error('WhatsApp send exception: ' . $e->getMessage());
            return null;
        }
    }
}
