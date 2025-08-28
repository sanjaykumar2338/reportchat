<?php
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminChatController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminMarketplaceCategoryController;
use App\Http\Controllers\AdminMarketplaceController;
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
    Route::get('/admin/users/{user}/reservations', [AdminUserController::class, 'reservationHistory'])->name('admin.users.reservationHistory');
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

    // Marketplace Categories
    Route::prefix('admin/marketplace_categories')->name('admin.marketplace_categories.')->group(function () {
        Route::get('/',            [AdminMarketplaceCategoryController::class,'index'])->name('index');
        Route::get('create',     [AdminMarketplaceCategoryController::class,'create'])->name('create');
        Route::post('/',           [AdminMarketplaceCategoryController::class,'store'])->name('store');
        Route::get('/{id}/edit',  [AdminMarketplaceCategoryController::class,'edit'])->name('edit');
        Route::put('/{id}',       [AdminMarketplaceCategoryController::class,'update'])->name('update');
        Route::delete('/{id}',    [AdminMarketplaceCategoryController::class,'destroy'])->name('destroy');
    });

    // Marketplace Listings
    Route::prefix('admin/marketplace')->name('admin.marketplace.')->group(function () {
        Route::get('/',            [AdminMarketplaceController::class,'index'])->name('index');
        Route::get('/create',     [AdminMarketplaceController::class,'create'])->name('create');
        Route::post('',           [AdminMarketplaceController::class,'store'])->name('store');
        Route::get('/{id}/edit',  [AdminMarketplaceController::class,'edit'])->name('edit');
        Route::put('/{id}',       [AdminMarketplaceController::class,'update'])->name('update');
        Route::delete('/{id}',    [AdminMarketplaceController::class,'destroy'])->name('destroy');
    });

    Route::get('/admin/reservations/calendar', [\App\Http\Controllers\ReservationController::class, 'calendar'])->name('admin.reservations.calendar');
    Route::get('/admin/reservations/events', [\App\Http\Controllers\ReservationController::class, 'calendarEvents'])->name('admin.reservations.events');
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

        // Check if user exists
        $user = User::where('username', $username)->first();

        if ($user) {
            // Update password
            $user->update([
                'password' => Hash::make($password)
            ]);
            $results[] = [
                'username' => $username,
                'status' => 'password updated',
                'email' => $user->email,
            ];
        } else {
            // Create new user
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
    }

    return response()->json($results);
});

Route::get('users/bulk-admin-register', function () {
    return;
    // ⚠️ One-time seed: remove this after running.
    $accounts = [
        ['email' => 'm.hesseldahl@lidcorp.mx', 'password' => 'A7d9L3x2#b5m'],
        ['email' => 'g.martinez@lidcorp.mx',   'password' => 'k2M8r7Yv!q4d'],
        ['email' => 'e.dana@lidcorp.mx',       'password' => 'Z4n7w2Tp@e9f'],
    ];

    $results = [];

    foreach ($accounts as $acc) {
        $email    = strtolower(trim($acc['email']));
        $password = $acc['password'];

        // Build a nice name from the local-part (e.g. "g.martinez" → "G Martinez")
        $local   = Str::before($email, '@');
        $name    = collect(preg_split('/[.\-_]+/', $local))
                    ->filter()->map(fn($p) => Str::ucfirst($p))->implode(' ');
        $name    = $name ?: Str::ucfirst(preg_replace('/[^a-z0-9]+/i', ' ', $local));

        // Base username from email (letters/numbers/underscore only)
        $base    = preg_replace('/[^a-z0-9_]/', '', Str::lower(str_replace(['.', '-'], '', $local)));
        $username = $base ?: Str::random(8);

        // Ensure username is unique if someone else already has it
        $suffix = 1;
        while (User::where('username', $username)->where('email', '!=', $email)->exists()) {
            $username = $base . $suffix++;
        }

        // Find by email (preferred). If exists, update; else create
        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update([
                'password' => Hash::make($password),
                'is_admin' => 1,
            ]);
            $status = 'contraseña actualizada y rol admin aplicado';
        } else {
            $user = User::create([
                'name'      => $name,
                'username'  => $username,
                'email'     => $email,
                'phone'     => null,
                'password'  => Hash::make($password),
                'is_admin'  => 1,
            ]);
            $status = 'registrado como admin';
        }

        $results[] = [
            'email'    => $email,
            'username' => $user->username,
            'status'   => $status,
        ];
    }

    return response()->json([
        'ok'        => true,
        'procesados'=> count($results),
        'resultados'=> $results,
    ]);
})->name('admin.users.bulkAdminRegister');

