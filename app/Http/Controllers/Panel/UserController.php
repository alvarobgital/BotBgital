<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email', 'role', 'created_at']);
        return response()->json($users);
    }

    public function store(Request $request)
    {
        // Permission check: only admin can create
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'No tienes permisos para crear usuarios'], 403);
        }

        // Limit check: max 3 users
        if (User::count() >= 3) {
            return response()->json(['message' => 'Límite de usuarios alcanzado (máximo 3)'], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,agent',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        return response()->json($user, 201);
    }

    public function destroy(Request $request, User $user)
    {
        // Permission check: only admin can delete
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'No tienes permisos para eliminar usuarios'], 403);
        }

        // Prevent self-deletion
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'No puedes eliminarte a ti mismo'], 422);
        }

        $user->delete();
        return response()->json(['status' => 'deleted']);
    }
}
