<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\SalesLead;
use Illuminate\Http\Request;

class SalesLeadController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesLead::with(['contact', 'assignedUser']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_type')) {
            $query->where('client_type', $request->client_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('plan_interest', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhereHas('contact', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                }
                );
            });
        }

        return response()->json(
            $query->orderByDesc('created_at')->paginate(20)
        );
    }

    public function update(Request $request, SalesLead $salesLead)
    {
        $validated = $request->validate([
            'status' => 'sometimes|string|in:pending,contacted,qualified,quoted,contracted,lost',
            'assigned_to' => 'sometimes|nullable|exists:users,id',
            'notes' => 'sometimes|nullable|string',
        ]);

        $salesLead->update($validated);
        return response()->json($salesLead->load(['contact', 'assignedUser']));
    }

    public function destroy(SalesLead $salesLead)
    {
        $salesLead->delete();
        return response()->json(['message' => 'Lead eliminado']);
    }

    public function stats()
    {
        return response()->json([
            'total' => SalesLead::count(),
            'pending' => SalesLead::where('status', 'pending')->count(),
            'contacted' => SalesLead::where('status', 'contacted')->count(),
            'qualified' => SalesLead::where('status', 'qualified')->count(),
            'quoted' => SalesLead::where('status', 'quoted')->count(),
            'contracted' => SalesLead::where('status', 'contracted')->count(),
            'lost' => SalesLead::where('status', 'lost')->count(),
        ]);
    }
}
