<?php
namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Log;

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
                'image' => 'nullable|string', // Accept single Base64 image string
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        // Process Base64 image if provided
        $imagePath = null;
        if (!empty($validatedData['image'])) {
            $imagePath = $this->saveBase64Image($validatedData['image']);
        }

        $chat = Chat::create([
            'user_id' => Auth::id(),
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'location' => $validatedData['location'],
            'phone' => $validatedData['phone'],
            'email' => $validatedData['email'],
            'image' => $imagePath, // Store correct image path
            'status' => 'open',
        ]);                

        return response()->json([
            'message' => 'Chat started successfully',
            'chat' => $chat
        ], 201);
    }

    // Get all messages in a chat, including images
    public function getMessages($chat_id)
    {
        $chat = Chat::where('id', $chat_id)
            ->where('user_id', Auth::id())
            ->with(['messages' => function ($query) {
                $query->select('id', 'chat_id', 'user_id', 'admin_id', 'message', 'image', 'created_at')
                    ->orderBy('created_at', 'asc');
            }])
            ->firstOrFail();

        // Ensure images are served with full URLs
        $chat->messages = $chat->messages->map(function ($message) {
            if (!empty($message->image)) {
                // Remove double slashes and fix URL formatting
                $message->image = asset(trim(str_replace('//', '/', $message->image), '/'));
            }
            return $message;
        });

        return response()->json([
            'chat' => $chat,
            'messages' => $chat->messages
        ]);
    }

    // Send a message (User)
    public function sendMessage(Request $request, $chat_id)
    {
        try {
            // Validate that either message or image is required
            $validatedData = $request->validate([
                'message' => 'nullable|string',
                'image' => 'nullable|string', // Accept base64 encoded images
            ]);

            // If both message and image are missing, return an error
            if (empty($validatedData['message']) && empty($validatedData['image'])) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['message' => ['Either a message or an image is required.']]
                ], 422);
            }

        } catch (ValidationException $e) {
            // Return validation errors as JSON response
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        // Find chat
        \Log::info('Broadcasting MessageSent Event', ['chat_id' => $chat_id]);
        $chat = Chat::where('id', $chat_id)->where('user_id', Auth::id())->firstOrFail();

        // Handle base64 image
        $imagePath = null;
        if (!empty($validatedData['image'])) {
            $imagePath = $this->saveBase64Image($validatedData['image']);
        }

        // Create new chat message
        $message = ChatMessage::create([
            'chat_id' => $chat->id,
            'user_id' => Auth::id(),
            'message' => $validatedData['message'] ?? null, // If empty, store null
            'image' => $imagePath,
            'is_admin' => false,
        ]);

        // Dispatch event for real-time broadcasting
        broadcast(new MessageSent($message))->toOthers();

        // Return success response
        return response()->json([
            'message' => 'Message sent successfully',
            'chat' => $message
        ], 201);
    }

    // Save Base64 encoded image to storage and return the URL
    private function saveBase64Image($base64String)
    {
        try {
            if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $matches)) {
                $imageType = $matches[1]; // Extract image type (png, jpg, etc.)
                $base64String = substr($base64String, strpos($base64String, ',') + 1);
                $base64String = base64_decode($base64String);
    
                if ($base64String === false) {
                    return null; // Invalid base64 data
                }
    
                // Generate unique file name
                $fileName = 'uploads/messages/' . uniqid() . '.' . $imageType;
    
                // Store image in Laravel storage (public disk)
                Storage::disk('public')->put($fileName, $base64String);
    
                // Return full URL of the stored image
                return asset('storage/' . $fileName);
            }
        } catch (\Exception $e) {
            Log::error('Base64 Image Save Error: ' . $e->getMessage());
            return null;
        }
    
        return null;
    }    

    // Admin sends a reply
    public function adminReply(Request $request, $chat_id)
    {
        try {
            $validatedData = $request->validate([
                'message' => 'nullable|string',
                'image' => 'nullable|string', // Accept Base64 encoded image
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        $chat = Chat::findOrFail($chat_id);

        $imagePath = null;
        if (!empty($validatedData['image'])) {
            $imagePath = $this->saveBase64Image($validatedData['image']);
        }

        $message = ChatMessage::create([
            'chat_id' => $chat->id,
            'admin_id' => Auth::id(),
            'message' => $validatedData['message'],
            'image' => $imagePath,
            'is_admin' => true,
        ]);

        // Broadcast the admin's message to user
        broadcast(new MessageSent($message))->toOthers();

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

    public function searchChats(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,solved,refused,open',
        ]);

        $query = Chat::query();

        // Filter by title if provided
        if (!empty($validatedData['title'])) {
            $query->where('title', 'LIKE', '%' . $validatedData['title'] . '%');
        }

        // Filter by status if provided
        if (!empty($validatedData['status'])) {
            $query->where('status', $validatedData['status']);
        }

        // Fetch filtered results
        $chats = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'message' => 'Chats retrieved successfully',
            'chats' => $chats
        ], 200);
    }
}