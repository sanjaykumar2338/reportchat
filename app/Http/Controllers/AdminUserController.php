<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Company;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();
        $query->where('email', '!=', 'testuser@example.com');

        // Apply filters if present
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }

        $users = $query->latest()->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.show', compact('user'));
    }

    public function dashboard()
    {
        $totalChats = Chat::count();
        $totalUsers = User::count();
        $activeSessions = User::whereNotNull('last_login_at')->count(); // Assuming active users based on login

        return view('admin.dashboard', compact('totalChats', 'totalUsers', 'activeSessions'));
    }

    public function create()
    {
        $companies = Company::all();
        return view('admin.users.edit', compact('companies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|exists:companies,id',
        ]);

        $validated['password'] = \Hash::make($request->password);

        User::create($validated);

        return redirect()->route('admin.users')->with('success', 'Usuario creado correctamente.');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $companies = Company::all();
        return view('admin.users.edit', compact('user', 'companies'));
    }


    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
            'company' => 'nullable|exists:companies,id',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = \Hash::make($request->password);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado correctamente.');
    }

    public function reservationHistory($userId)
    {
        $user = User::with('reservations.room')->findOrFail($userId);
        return view('admin.users.reservation_history', compact('user'));
    }
}
