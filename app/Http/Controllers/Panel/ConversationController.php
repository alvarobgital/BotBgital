<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $query = Conversation::with(['contact', 'latestMessage', 'assignedAgent'])
            ->orderByDesc('updated_at');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('contact', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $conversations = $query->paginate(50);

        return response()->json($conversations);
    }

    public function show(Conversation $conversation)
    {
        $conversation->load(['contact', 'assignedAgent', 'messages' => function ($q) {
            $q->orderBy('created_at');
        }]);

        return response()->json($conversation);
    }

    public function assignAgent(Request $request, Conversation $conversation)
    {
        $request->validate(['agent_id' => 'required|exists:users,id']);

        $conversation->update([
            'assigned_agent_id' => $request->agent_id,
            'status' => 'agent_active',
        ]);

        return response()->json($conversation->fresh(['contact', 'assignedAgent']));
    }

    public function takeOver(Request $request, Conversation $conversation)
    {
        $conversation->update([
            'assigned_agent_id' => $request->user()->id,
            'status' => 'agent_active',
        ]);

        return response()->json($conversation->fresh(['contact', 'assignedAgent']));
    }

    public function reactivateBot(Conversation $conversation)
    {
        $conversation->update([
            'status' => 'bot_active',
            'assigned_agent_id' => null,
        ]);

        return response()->json($conversation->fresh(['contact', 'assignedAgent']));
    }

    public function close(Conversation $conversation)
    {
        $conversation->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return response()->json($conversation->fresh(['contact', 'assignedAgent']));
    }

    public function destroy(Conversation $conversation)
    {
        $conversation->messages()->delete();
        \App\Models\SalesLead::where('conversation_id', $conversation->id)->update(['conversation_id' => null]);
        $conversation->delete();
        return response()->json(['message' => 'Conversation deleted successfully']);
    }
}
