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

    /**
     * Send a plain text message
     */
    public function sendText(string $to, string $message): ?array
    {
        return $this->sendRequest([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $message],
        ]);
    }

    /**
     * Send interactive buttons (max 3)
     * $buttonsKeyValue = ['btn_id' => 'Button Title', ...]
     */
    public function sendInteractiveButtons(string $to, string $bodyText, array $buttonsKeyValue): ?array
    {
        $formattedButtons = collect($buttonsKeyValue)->take(3)->map(function ($title, $id) {
            return [
            'type' => 'reply',
            'reply' => [
            'id' => (string)$id,
            'title' => mb_substr($title, 0, 20, 'UTF-8'),
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

    /**
     * Send interactive list message (up to 10 rows per section, 10 sections)
     * Perfect for plan catalogues that exceed 3 options
     *
     * $sections = [
     *   ['title' => 'Section Title', 'rows' => [
     *     ['id' => 'row_id', 'title' => 'Row Title', 'description' => 'Optional desc'],
     *   ]]
     * ]
     */
    public function sendInteractiveList(string $to, string $bodyText, array $sections, string $buttonText = 'Ver Opciones'): ?array
    {
        // Ensure titles are within WhatsApp limits
        $formattedSections = [];
        foreach (array_slice($sections, 0, 10) as $section) {
            $rows = [];
            foreach (array_slice($section['rows'] ?? [], 0, 10) as $row) {
                $rows[] = [
                    'id' => mb_substr($row['id'] ?? uniqid(), 0, 200, 'UTF-8'),
                    'title' => mb_substr($row['title'] ?? '', 0, 24, 'UTF-8'),
                    'description' => mb_substr($row['description'] ?? '', 0, 72, 'UTF-8'),
                ];
            }
            $formattedSections[] = [
                'title' => mb_substr($section['title'] ?? '', 0, 24, 'UTF-8'),
                'rows' => $rows,
            ];
        }

        return $this->sendRequest([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list',
                'body' => ['text' => $bodyText],
                'action' => [
                    'button' => mb_substr($buttonText, 0, 20, 'UTF-8'),
                    'sections' => $formattedSections,
                ],
            ],
        ]);
    }

    /**
     * Send media (image, video, document, audio)
     */
    public function sendMedia(string $to, string $mediaUrl, string $mediaType, ?string $caption = null): ?array
    {
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
        // Fix Mexican number +521 issue for WhatsApp API Testing
        // The webhook receives '521...', but the Meta allowed list requires '52...'
        if (isset($payload['to']) && preg_match('/^521(\d{10})$/', $payload['to'], $matches)) {
            $payload['to'] = '52' . $matches[1];
        }

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
