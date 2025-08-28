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
        $query = User::query();
        $query = User::query()
        ->where('role', '!=', 'superadmin');

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
            'name'     => 'required',
            'username' => 'required|unique:users',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role'     => 'nullable|in:user,admin,superadmin',
            'company'  => 'nullable|exists:companies,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = $validated['role'] ?? 'user';
        $validated['company_id'] = $validated['company'] ?? null;

        if ($validated['role'] === 'admin' && $request->has('permissions')) {
            $keys = array_keys($request->input('permissions', []));
            $validated['permissions'] = array_fill_keys($keys, true);
        }

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
            'name'     => 'required',
            'username' => 'required|unique:users,username,' . $user->id,
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
            'role'     => 'nullable|in:user,admin,superadmin',
            'company'  => 'nullable|exists:companies,id',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['company_id'] = $validated['company'] ?? null;
        $validated['role'] = $validated['role'] ?? $user->role;

        if ($validated['role'] === 'admin' && $request->has('permissions')) {
            $keys = array_keys($request->input('permissions', []));
            $validated['permissions'] = array_fill_keys($keys, true);
        } else {
            $validated['permissions'] = null;
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if($user->username=='mario2025'){
            return redirect()->route('admin.users.index')->with('error', 'No se puede eliminar el usuario predeterminado.');
        }

        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado correctamente.');
    }

    public function reservationHistory($userId)
    {
        $user = User::with('reservations.room')->findOrFail($userId);
        return view('admin.users.reservation_history', compact('user'));
    }

    public function profile()
    {
        $user = auth()->user();
        $companies = \App\Models\Company::all(); // optional if you want company select
        return view('admin.users.profile', compact('user', 'companies'));
    }

    public function updateProfile(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'phone'    => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
            'company'  => 'nullable|exists:companies,id',
        ]);

        // normalize
        $validated['email']    = strtolower(trim($validated['email']));
        $validated['username'] = trim($validated['username']);

        // map company select to company_id (optional)
        $validated['company_id'] = $validated['company'] ?? $user->company_id ?? null;

        // handle password only if provided
        if (!empty($validated['password'])) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        unset($validated['company']); // not a column

        $user->update($validated);

        return redirect()->route('admin.profile')->with('success', 'Perfil actualizado correctamente.');
    }
}
