<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::with('services');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('services', function ($sq) use ($search) {
                    $sq->where('account_number', 'like', "%{$search}%")
                        ->orWhere('label', 'like', "%{$search}%");
                }
                );
            });
        }

        return response()->json($query->orderBy('name')->paginate(20));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'account_number' => 'required|string|unique:customer_services,account_number',
            'plan_name' => 'nullable|string|max:255',
            'label' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'zip_code' => 'nullable|string|max:10',
        ]);

        $customer = Customer::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'address' => $validated['address'] ?? null,
            'zip_code' => $validated['zip_code'] ?? null,
        ]);

        $customer->services()->create([
            'account_number' => $validated['account_number'],
            'plan_name' => $validated['plan_name'] ?? null,
            'label' => $validated['label'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        return response()->json($customer->load('services'), 201);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|nullable|string|max:500',
            'zip_code' => 'sometimes|nullable|string|max:10',
        ]);

        $customer->update($validated);
        return response()->json($customer->load('services'));
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->json(['message' => 'Cliente eliminado']);
    }

    // --- Service CRUD ---

    public function addService(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'account_number' => 'required|string|unique:customer_services,account_number',
            'plan_name' => 'nullable|string|max:255',
            'label' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $service = $customer->services()->create($validated);
        return response()->json($service, 201);
    }

    public function updateService(Request $request, CustomerService $service)
    {
        $validated = $request->validate([
            'account_number' => 'sometimes|string|unique:customer_services,account_number,' . $service->id,
            'plan_name' => 'sometimes|nullable|string|max:255',
            'label' => 'sometimes|nullable|string|max:255',
            'address' => 'sometimes|nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        $service->update($validated);
        return response()->json($service);
    }

    public function removeService(CustomerService $service)
    {
        $service->delete();
        return response()->json(['message' => 'Servicio eliminado']);
    }
}
