<?php
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminChatController;
use App\Http\Controllers\AdminUserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;

use App\Models\User;

// Admin Login Routes (Using Sessions)
Route::get('/', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');

Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::get('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Admin Panel Routes (Protected)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminUserController::class, 'dashboard'])->name('admin.dashboard');

    Route::get('/admin/chats', [AdminChatController::class, 'index'])->name('admin.chats');
    Route::get('/admin/chat/{chat_id}', [AdminChatController::class, 'viewChat'])->name('admin.view.chat');
    Route::post('/admin/chat/{chat_id}/send', [AdminChatController::class, 'sendMessage'])->name('admin.send.message');
    Route::post('/admin/chats/{chat_id}/update-status', [AdminChatController::class, 'updateStatus'])->name('admin.update.status');
    Route::get('/admin/chats/{chat_id}/messages', [AdminChatController::class, 'fetchMessages'])->name('admin.fetch.messages');
    Route::post('/admin/chats/{chat_id}/messages', [AdminChatController::class, 'sendMessage'])
    ->name('admin.send.message');

    // Admin Users Management
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users');
    Route::get('/admin/users/{id}', [AdminUserController::class, 'show'])->name('admin.users.show');
});

Route::get('/bulk-register', function () {
    $users = [
        'amaya2025' => 'agp210325',
        'vanesa2025' => 'agp210325',
        'ARTURO2025' => 'agp210325',
        'mario2025' => 'agp210325',
        'Guillermo2025' => 'agp210325',
        'Ivan2025' => 'agp210325',
        'Natalia2025' => 'agp210325',
        'victor2025' => 'agp210325',
    ];

    $results = [];

    foreach ($users as $username => $password) {
        $email = strtolower($username) . '@gmail.com';

        // Skip if username already exists
        if (User::where('username', $username)->exists()) {
            $results[] = [
                'username' => $username,
                'status' => 'already exists',
            ];
            continue;
        }

        // Create the user
        $user = User::create([
            'name' => ucfirst(strtolower($username)),
            'username' => $username,
            'email' => $email,
            'phone' => null,
            'password' => Hash::make($password),
        ]);

        $results[] = [
            'username' => $username,
            'status' => 'registered',
            'email' => $email,
        ];
    }

    return response()->json($results);
});