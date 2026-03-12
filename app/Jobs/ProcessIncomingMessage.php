<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\Conversation;
use App\Services\BotEngineService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessIncomingMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Message $message;
    public Conversation $conversation;

    public function __construct(Message $message, Conversation $conversation)
    {
        $this->message = $message;
        $this->conversation = $conversation;
    }

    public function handle(): void
    {
        // Only process if conversation is bot_active
        if ($this->conversation->status !== 'bot_active') {
            return;
        }

        try {
            $botEngine = new BotEngineService();
            $response = $botEngine->handleSequence(
                $this->conversation,
                $this->message->content
            );
        } catch (\Throwable $e) {
            Log::error('ProcessIncomingMessage: handleSequence failed', [
                'conversation_id' => $this->conversation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Optional: return a fallback response
            $response = ['type' => 'text', 'text' => "Lo siento, tuve un error interno. Por favor escribe *reiniciar*."];
        }

        if (empty($response)) {
            return;
        }

        $whatsApp = new WhatsAppService();
        $phone = $this->conversation->contact->phone;
        $responseText = $response['text'] ?? '';

        try {
            $textSentAsCaption = false;

            if (!empty($response['media_path'])) {
                $mediaUrl = asset($response['media_path']);
                // If it's just text, we send it as a caption. If it has buttons/lists, we send media separately.
                if (empty($response['list_sections']) && empty($response['buttons'])) {
                    $whatsApp->sendMedia($phone, $mediaUrl, $response['media_type'], $responseText);
                    $textSentAsCaption = true;
                }
                else {
                    $whatsApp->sendMedia($phone, $mediaUrl, $response['media_type']);
                }
            }

            // Determine message type and send accordingly
            if (!empty($response['list_sections']) && is_array($response['list_sections'])) {
                // WhatsApp Interactive List
                $whatsApp->sendInteractiveList(
                    $phone,
                    $responseText,
                    $response['list_sections'],
                    $response['list_button_text'] ?? 'Ver Opciones'
                );
            }
            elseif (!empty($response['buttons']) && is_array($response['buttons'])) {
                // WhatsApp Interactive Buttons (max 3)
                $buttonMap = [];
                foreach (array_slice($response['buttons'], 0, 3) as $btn) {
                    $id = $btn['reply']['id'] ?? $btn['id'] ?? uniqid();
                    $title = $btn['reply']['title'] ?? $btn['title'] ?? 'Opción';
                    $buttonMap[$id] = $title;
                }
                $whatsApp->sendInteractiveButtons($phone, $responseText, $buttonMap);
            }
            elseif (!$textSentAsCaption && $responseText !== '') {
                // Plain text Only
                $whatsApp->sendText($phone, $responseText);
            }

            // Save outbound message
            Message::create([
                'conversation_id' => $this->conversation->id,
                'direction' => 'outbound',
                'sender_type' => 'bot',
                'content' => $responseText,
            ]);

        }
        catch (\Exception $e) {
            Log::error('Bot response failed', [
                'conversation_id' => $this->conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
