<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // User Registration
    public function register(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users',
                'email' => 'nullable|string|email|max:255|unique:users',
                'phone' => 'nullable|string|max:15',
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
            'name'       => $validatedData['name'],
            'username'   => $validatedData['username'],
            'email'      => $validatedData['email'] ?? null,
            'phone'      => $validatedData['phone'] ?? null,
            'fcm_token'  => $validatedData['fcm_token'] ?? null,
            'password'   => Hash::make($validatedData['password']),
        ]);

        return response()->json([
            'message' => 'Usuario registrado con éxito',
            'user'    => $user
        ], 201);
    }

    // User Login (Username or Email)
    public function login(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'username'     => 'required|string', // Can be username OR email
                'password'  => 'required|string',
                'fcm_token' => 'nullable'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);
        }

        // Allow login with username OR email
        $user = User::where('username', $validatedData['username'])
            ->orWhere('email', $validatedData['username'])
            ->first();

        if (!$user || !Hash::check($validatedData['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        // Create Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Update FCM token if provided
        if (!empty($validatedData['fcm_token'])) {
            $user->update(['fcm_token' => $validatedData['fcm_token']]);
        }

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'user'    => $user,
            'token'   => $token
        ], 200);
    }

    // Logout User
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }
}
