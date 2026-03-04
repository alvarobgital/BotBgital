<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::with('contact')->latest()->get();
        return response()->json($tickets);
    }

    public function show(Ticket $ticket)
    {
        $ticket->load('contact');
        return response()->json($ticket);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:open,in_progress,resolved,closed',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'resolution_notes' => 'nullable|string',
        ]);

        $ticket->update($validated);

        return response()->json($ticket);
    }
}
