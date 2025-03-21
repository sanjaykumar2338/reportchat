<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Chat;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

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
}
