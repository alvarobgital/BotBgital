<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingMessage;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * GET — Verify webhook with Meta
     */
    public function verify(Request $request)
    {
        $verifyToken = config('services.whatsapp.webhook_verify_token');

        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            file_put_contents(base_path('bot_debug.log'), date('[Y-m-d H:i:s]') . " Webhook: Verification SUCCESS\n", FILE_APPEND);
            Log::info('WhatsApp webhook verified');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        file_put_contents(base_path('bot_debug.log'), date('[Y-m-d H:i:s]') . " Webhook: Verification FAILED. Expected: {$verifyToken}, Got: {$token}\n", FILE_APPEND);

        return response('Forbidden', 403);
    }

    /**
     * POST — Receive incoming messages
     */
    public function receive(Request $request)
    {
        $payload = $request->all();
        $rawContent = $request->getContent();
        file_put_contents(base_path('bot_debug.log'), date('[Y-m-d H:i:s]') . " Webhook: RAW=" . $rawContent . " | ALL=" . json_encode($payload) . "\n", FILE_APPEND);

        Log::info('WhatsApp webhook received', ['payload' => $payload]);

        // Extract message data
        $entry = $payload['entry'][0] ?? null;
        if (!$entry) {
            return response()->json(['status' => 'ok']);
        }

        $changes = $entry['changes'][0] ?? null;
        $value = $changes['value'] ?? null;

        if (!$value || !isset($value['messages'])) {
            // Could be a status update, not a message
            return response()->json(['status' => 'ok']);
        }

        $messageData = $value['messages'][0];
        $contactData = $value['contacts'][0] ?? null;
        $from = $messageData['from']; // Phone number
        $messageId = $messageData['id'];

        // Extract text content
        $text = '';
        if ($messageData['type'] === 'text') {
            $text = $messageData['text']['body'] ?? '';
        }
        elseif ($messageData['type'] === 'interactive') {
            // Button reply
            $text = $messageData['interactive']['button_reply']['id']
                ?? $messageData['interactive']['button_reply']['title']
                ?? $messageData['interactive']['list_reply']['id']
                ?? '';
        }
        else {
            $text = '[' . $messageData['type'] . ']';
        }

        // Create or update contact
        $contact = Contact::updateOrCreate(
        ['phone' => $from],
        [
            'name' => $contactData['profile']['name'] ?? null,
            'platform' => 'whatsapp',
        ]
        );

        // Get or create active conversation
        $conversation = Conversation::where('contact_id', $contact->id)
            ->whereIn('status', ['bot_active', 'waiting_agent', 'agent_active'])
            ->latest()
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'contact_id' => $contact->id,
                'status' => 'bot_active',
                'started_at' => now(),
            ]);
        }

        // Save inbound message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'sender_type' => 'client',
            'content' => $text,
            'meta_message_id' => $messageId,
        ]);

        // Dispatch to queue for processing
        ProcessIncomingMessage::dispatch($message, $conversation);

        return response()->json(['status' => 'ok']);
    }
}
