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

        $admin = User::where('email', $request->email)->where('is_admin', true)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return back()->withErrors(['email' => 'Invalid admin credentials']);
        }

        Auth::login($admin);

        return redirect()->route('admin.dashboard');
    }

    // Logout Admin
    public function logout()
    {
        Auth::logout();
        return redirect()->route('admin.login');
    }
}