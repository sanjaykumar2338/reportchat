<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminAuthController extends Controller
{
    // Show Admin Login Form
    public function showLoginForm()
    {
        //echo \Hash::make('admin@123'); die;
        return view('admin.login');
    }

    // Handle Admin Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Invalid admin credentials']);
        }

        // block normal users
        if ($user->role === 'user') {
            return back()->withErrors(['email' => 'No tienes acceso al panel de administración.']);
        }

        Auth::login($user);

        // ✅ If superadmin → go to dashboard
        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        // ✅ If admin → check permissions
        if ($user->isAdmin()) {
            $perms = $user->permissions ?? [];

            $map = [
                'dashboard'             => 'admin.dashboard',
                'reports'               => 'admin.chats',
                'users'                 => 'admin.users.index',
                'companies'             => 'admin.companies.index',
                'rooms'                 => 'admin.rooms.index',
                'reservations'          => 'admin.reservations.index',
                'marketplace_categories'=> 'admin.marketplace_categories.index',
                'marketplace'           => 'admin.marketplace.index',
            ];

            foreach ($map as $perm => $route) {
                if (!empty($perms[$perm])) {
                    return redirect()->route($route);
                }
            }

            // if no permission at all → profile
            return redirect()->route('admin.profile');
        }

        // fallback
        return redirect()->route('admin.profile');
    }

    // Logout Admin
    public function logout()
    {
        Auth::logout();
        return redirect()->route('admin.login');
    }
}