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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'customer_service_id' => 'nullable|exists:customer_services,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|in:open,in_progress,resolved,closed',
            'priority' => 'required|in:low,medium,high,urgent',
            'category' => 'nullable|string|max:100',
        ]);

        $ticket = Ticket::create($validated);
        return response()->json($ticket->load('contact'), 201);
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

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();
        return response()->json(['message' => 'Ticket eliminado']);
    }
}
