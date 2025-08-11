<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Chat;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->where('email', '!=', 'testuser@example.com');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%'.$request->email.'%');
        }

        // (Optional) allow searching by username too:
        if ($request->filled('username')) {
            $query->where('username', 'like', '%'.$request->username.'%');
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
        $totalChats     = Chat::count();
        $totalUsers     = User::count();
        $activeSessions = User::whereNotNull('last_login_at')->count();

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
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone'    => 'nullable|string|max:20',
            'company'  => 'nullable|exists:companies,id', // select field name in your Blade
        ]);

        // Normalize
        $validated['email']    = strtolower(trim($validated['email']));
        $validated['username'] = trim($validated['username']);

        // Map company select -> company_id column (adjust if your column is different)
        if (!empty($validated['company'])) {
            $validated['company_id'] = $validated['company'];
        }
        unset($validated['company']);

        // Hash password
        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('admin.users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit($id)
    {
        $user      = User::findOrFail($id);
        $companies = Company::all();
        return view('admin.users.edit', compact('user', 'companies'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'phone'    => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
            'company'  => 'nullable|exists:companies,id',
        ]);

        // Normalize
        if (isset($validated['email'])) {
            $validated['email'] = strtolower(trim($validated['email']));
        }
        if (isset($validated['username'])) {
            $validated['username'] = trim($validated['username']);
        }

        // Map company select -> company_id
        if (!empty($validated['company'])) {
            $validated['company_id'] = $validated['company'];
        }
        unset($validated['company']);

        // Hash password only if present
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
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
