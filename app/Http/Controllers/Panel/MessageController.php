<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function send(Request $request, Conversation $conversation)
    {
        $request->validate([
            'content' => 'required|string|max:4096',
        ]);

        $whatsApp = new WhatsAppService();
        $result = $whatsApp->sendText(
            $conversation->contact->phone,
            $request->content
        );

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'sender_type' => 'agent',
            'content' => $request->content,
            'meta_message_id' => $result['messages'][0]['id'] ?? null,
        ]);

        return response()->json($message);
    }

    public function markAsRead(Conversation $conversation)
    {
        $conversation->messages()
            ->where('direction', 'inbound')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['status' => 'ok']);
    }
}
