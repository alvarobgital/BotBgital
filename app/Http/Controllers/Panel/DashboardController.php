<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Contact;
use App\Models\Message;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_conversations' => Conversation::count(),
            'active_bot' => Conversation::where('status', 'bot_active')->count(),
            'waiting_agent' => Conversation::where('status', 'waiting_agent')->count(),
            'agent_active' => Conversation::where('status', 'agent_active')->count(),
            'closed' => Conversation::where('status', 'closed')->count(),
            'total_contacts' => Contact::count(),
            'messages_today' => Message::whereDate('created_at', today())->count(),
            'recent_conversations' => Conversation::with(['contact', 'latestMessage'])
            ->orderByDesc('updated_at')
            ->take(10)
            ->get(),
        ];

        return response()->json($stats);
    }
}
