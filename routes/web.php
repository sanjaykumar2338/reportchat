<?php
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminChatController;
use App\Http\Controllers\AdminUserController;
use Illuminate\Support\Facades\Route;

// Admin Login Routes (Using Sessions)
Route::get('/', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Admin Panel Routes (Protected)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminUserController::class, 'dashboard'])->name('admin.dashboard');

    Route::get('/admin/chats', [AdminChatController::class, 'index'])->name('admin.chats');
    Route::get('/admin/chat/{chat_id}', [AdminChatController::class, 'viewChat'])->name('admin.view.chat');
    Route::post('/admin/chat/{chat_id}/send', [AdminChatController::class, 'sendMessage'])->name('admin.send.message');

    // Admin Users Management
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users');
    Route::get('/admin/users/{id}', [AdminUserController::class, 'show'])->name('admin.users.show');
});
