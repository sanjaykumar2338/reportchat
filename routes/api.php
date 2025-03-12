<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (\Illuminate\Http\Request $request) {
        return $request->user();
    });

    Route::post('/chats', [ChatController::class, 'startChat']); // Start a chat
    Route::get('/chats/{chat_id}/messages', [ChatController::class, 'getMessages']); // Get chat messages
    Route::post('/chats/{chat_id}/messages', [ChatController::class, 'sendMessage']); // User sends message
    Route::post('/chats/{chat_id}/admin-reply', [ChatController::class, 'adminReply']); // Admin replies
    Route::get('/chats', [ChatController::class, 'getChatsList']);
});
