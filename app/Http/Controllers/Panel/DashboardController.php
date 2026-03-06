<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Contact;
use App\Models\Message;
use App\Models\Customer;
use App\Models\CustomerService;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $stats = [
            // Conversation stats
            'conversations_today' => Conversation::whereDate('created_at', $today)->count(),
            'active_bot' => Conversation::where('status', 'bot_active')->count(),
            'waiting_agent' => Conversation::where('status', 'waiting_agent')->count(),
            'agent_active' => Conversation::where('status', 'agent_active')->count(),
            'total_conversations' => Conversation::count(),
            'closed' => Conversation::where('status', 'closed')->count(),
            'messages_today' => Message::whereDate('created_at', $today)->count(),

            // Customers & services
            'total_customers' => Customer::count(),
            'active_services' => CustomerService::where('is_active', true)->count(),
            'suspended_services' => CustomerService::where('is_active', false)->count(),

            // Contacts / Leads
            'total_contacts' => Contact::count(),
            'new_leads_today' => Contact::whereDate('created_at', $today)->count(),

            // Tickets
            'tickets_open' => Ticket::where('status', 'open')->count(),
            'tickets_in_progress' => Ticket::whereIn('status', ['open', 'in_progress'])->count(),

            // Bot handled today (conversations that were created and closed today by bot)
            'bot_handled' => Conversation::where('status', 'closed')
            ->whereDate('created_at', $today)
            ->count(),

            // Activity chart — last 7 days
            'conversations_by_day' => $this->getConversationsByDay(7),

            // Recent activity
            'recent_conversations' => Conversation::with(['contact', 'latestMessage'])
            ->orderByDesc('updated_at')
            ->take(8)
            ->get(),
        ];

        return response()->json($stats);
    }

    private function getConversationsByDay(int $days): array
    {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $data[] = [
                'date' => $date->format('D'),
                'count' => Conversation::whereDate('created_at', $date)->count(),
            ];
        }
        return $data;
    }
}
