<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MarketplaceController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/login', function () {
    return response()->json(['message' => 'Unauthorized'], 401);
})->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (\Illuminate\Http\Request $request) {
        return $request->user();
    });

    Route::post('/chats', [ChatController::class, 'startChat']); // Start a chat
    Route::post('/chats/update', [ChatController::class, 'updateChat'])->name('chats.update');
    Route::get('/chats/{chat_id}/messages', [ChatController::class, 'getMessages']); // Get chat messages
    Route::post('/chats/{chat_id}/messages', [ChatController::class, 'sendMessage']); // User sends message
    Route::post('/chats/{chat_id}/admin-reply', [ChatController::class, 'adminReply']); // Admin replies
    Route::get('/chats', [ChatController::class, 'getChatsList']);
    Route::post('/chats/search', [ChatController::class, 'searchChats']); // Search chats

    Route::get('/notifications/{id?}', [\App\Http\Controllers\NotificationController::class, 'userNotifications']);
    Route::delete('/notifications/{id}', [\App\Http\Controllers\NotificationController::class, 'deleteNotification']);
    Route::delete('/notifications', [\App\Http\Controllers\NotificationController::class, 'clearAllNotifications']);

    Route::get('/rooms', [\App\Http\Controllers\Api\RoomApiController::class, 'index']);
    Route::post('/reservations', [\App\Http\Controllers\Api\RoomApiController::class, 'store']);
    Route::get('/reservations/availability', [\App\Http\Controllers\Api\RoomApiController::class, 'checkAvailability']);
    Route::get('/my/reservations', [\App\Http\Controllers\Api\RoomApiController::class, 'profileWithReservations']);
    Route::get('/reservations/cancel', [\App\Http\Controllers\Api\RoomApiController::class, 'cancelReservation']);

    // Marketplace Categories
    Route::get('/marketplace/categories', [MarketplaceController::class, 'categories']);

    // Listings
    Route::get('/marketplace/listings', [MarketplaceController::class, 'index']); // List all active listings in a category
    Route::post('/marketplace/listings', [MarketplaceController::class, 'store']); // Create new listing
    Route::get('/marketplace/listings/{id}', [MarketplaceController::class, 'show']); // Show single listing
    Route::put('/marketplace/listings/{id}', [MarketplaceController::class, 'update']); // Update listing
    Route::delete('/marketplace/listings/{id}', [MarketplaceController::class, 'destroy']); // Delete listing

    // Toggle listing status (Activate/Deactivate)
    Route::post('/marketplace/listings/{id}/toggle', [MarketplaceController::class, 'toggleStatus']);

    // Republish listing
    Route::post('/marketplace/listings/{id}/republish', [MarketplaceController::class, 'republish']);

    // My Listings (Active + Inactive)
    Route::get('/marketplace/my-listings', [MarketplaceController::class, 'myListings']);
});


