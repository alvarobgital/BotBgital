<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $query = Contact::with('latestConversation')
            ->orderByDesc('updated_at');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        }

        return response()->json($query->paginate(50));
    }

    public function show(Contact $contact)
    {
        $contact->load(['conversations.latestMessage', 'conversations.assignedAgent']);
        return response()->json($contact);
    }

    public function update(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
        ]);

        $contact->update($validated);
        return response()->json($contact);
    }
}
