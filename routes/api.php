<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

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
    Route::post('/reservation', [\App\Http\Controllers\Api\RoomApiController::class, 'store']);
    Route::get('/reservation/availability', [\App\Http\Controllers\Api\RoomApiController::class, 'checkAvailability']);
});


