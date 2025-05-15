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

    // Admin Chat Routes
    Route::get('/admin/chats', [AdminChatController::class, 'index'])->name('admin.chats');
    Route::get('/admin/chat/{chat_id}', [AdminChatController::class, 'viewChat'])->name('admin.view.chat');
    Route::post('/admin/chat/{chat_id}/send', [AdminChatController::class, 'sendMessage'])->name('admin.send.message');
    Route::post('/admin/chats/{chat_id}/update-status', [AdminChatController::class, 'updateStatus'])->name('admin.update.status');
    Route::get('/admin/chats/{chat_id}/messages', [AdminChatController::class, 'fetchMessages'])->name('admin.fetch.messages');
    Route::post('/admin/chats/{chat_id}/messages', [AdminChatController::class, 'sendMessage'])->name('admin.send.message');

    // Admin Users Management
    Route::resource('/admin/users', \App\Http\Controllers\AdminUserController::class)->names('admin.users');
    Route::post('/admin/companies/send-notification', [\App\Http\Controllers\AdminCompanyController::class, 'sendNotification'])->name('admin.companies.sendNotification');
    Route::resource('admin/companies', \App\Http\Controllers\AdminCompanyController::class)->names('admin.companies');

    // Admin Room Management Routes
    Route::prefix('admin/rooms')->name('admin.rooms.')->group(function () {
        Route::get('/', [\App\Http\Controllers\RoomController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\RoomController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\RoomController::class, 'store'])->name('store');
        Route::get('/{room}/edit', [\App\Http\Controllers\RoomController::class, 'edit'])->name('edit');
        Route::put('/{room}', [\App\Http\Controllers\RoomController::class, 'update'])->name('update');
        Route::delete('/{room}', [\App\Http\Controllers\RoomController::class, 'destroy'])->name('destroy');
    });

    // Admin Reservation Management Routes
    Route::prefix('admin/reservations')->name('admin.reservations.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ReservationController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\ReservationController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\ReservationController::class, 'store'])->name('store');
        Route::get('/{reservation}/edit', [\App\Http\Controllers\ReservationController::class, 'edit'])->name('edit');
        Route::put('/{reservation}', [\App\Http\Controllers\ReservationController::class, 'update'])->name('update');
        Route::delete('/{reservation}', [\App\Http\Controllers\ReservationController::class, 'destroy'])->name('destroy');
    });
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