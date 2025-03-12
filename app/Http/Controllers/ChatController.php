<?php
namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    // Start a new chat with automatic questions
    public function startChat(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'location' => 'required|string|max:255',
                'phone' => 'required|string|max:15',
                'email' => 'required|string|email|max:255',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        // Upload images
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('uploads/chats', 'public');
                $imagePaths[] = Storage::url($path);
            }
        }

        $chat = Chat::create([
            'user_id' => Auth::id(),
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'location' => $validatedData['location'],
            'phone' => $validatedData['phone'],
            'email' => $validatedData['email'],
            'images' => $imagePaths,
            'status' => 'open',
        ]);

        return response()->json(['message' => 'Chat started successfully', 'chat' => $chat], 201);
    }

    // Get chat messages
    public function getMessages($chat_id)
    {
        $chat = Chat::with('messages')->where('id', $chat_id)->where('user_id', Auth::id())->firstOrFail();
        return response()->json($chat->messages);
    }

    // Send a message
    public function sendMessage(Request $request, $chat_id)
    {
        try {
            $validatedData = $request->validate([
                'message' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        $chat = Chat::where('id', $chat_id)->where('user_id', Auth::id())->firstOrFail();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = Storage::url($request->file('image')->store('uploads/messages', 'public'));
        }

        $message = ChatMessage::create([
            'chat_id' => $chat->id,
            'user_id' => Auth::id(),
            'message' => $validatedData['message'],
            'image' => $imagePath,
            'is_admin' => false,
        ]);

        return response()->json(['message' => 'Message sent', 'chat' => $message], 201);
    }

    // Admin replies to the user
    public function adminReply(Request $request, $chat_id)
    {
        try {
            $validatedData = $request->validate([
                'message' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        $chat = Chat::findOrFail($chat_id);

        $message = ChatMessage::create([
            'chat_id' => $chat->id,
            'admin_id' => Auth::id(),
            'message' => $validatedData['message'],
            'is_admin' => true,
        ]);

        return response()->json(['message' => 'Admin reply sent', 'chat' => $message], 201);
    }

    // Get all chats list with title, location, and status
    public function getChatsList()
    {
        $chats = Chat::where('user_id', Auth::id()) // Fetch chats for logged-in user
                    ->select('id', 'title', 'location', 'status', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->get();

        return response()->json([
            'message' => 'Chats retrieved successfully',
            'chats' => $chats
        ], 200);
    }
}