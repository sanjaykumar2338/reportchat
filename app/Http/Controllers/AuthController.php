<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // User Registration
    public function register(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users', // Validate username
                'email' => 'required|string|email|max:255|unique:users', // Email is optional
                'phone' => 'nullable|string|max:15', // Phone is optional
                'password' => 'required|string|min:6|confirmed',
                'fcm_token' => 'nullable'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $validatedData['name'],
            'username' => $validatedData['username'],
            'email' => $validatedData['email'] ?? null,
            'phone' => $validatedData['phone'] ?? null,
            'fcm_token' => $validatedData['fcm_token'] ?? null,
            'password' => Hash::make($validatedData['password']),
        ]);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user
        ], 201);
    }

    // User Login (Login with either Username or Email)
    public function login(Request $request)
    {
        Log::info('Login API Request Details', [
            'method'       => $request->method(),
            'full_url'     => $request->fullUrl(),
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->header('User-Agent'),
            'headers'      => $request->headers->all(),
            'query_params' => $request->query(),
            'body'         => collect($request->all())->toArray()
        ]);

        try {
            $validated = $request->validate([
                'username'  => 'required|string',   // can be username or email
                'password'  => 'required|string',
                'fcm_token' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 422,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        }

        $loginId = trim($validated['username']);
        // Try username first, then email
        $user = \App\Models\User::where('username', $loginId)
            ->orWhere('email', strtolower($loginId))
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            Log::warning('Invalid credentials', ['loginId' => $loginId]);
            return response()->json([
                'status'  => 401,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Optional: update last_login_at
        // $user->last_login_at = now();

        if (!empty($validated['fcm_token'])) {
            $user->fcm_token = $validated['fcm_token'];
        }

        $user->save();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => 200,
            'message' => 'Login successful',
            'user'    => $user,
            'token'   => $token,
        ], 200);
    }

    // Logout User
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logout successful']);
    }
}