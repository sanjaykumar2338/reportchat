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
use Carbon\Carbon;

class ChatController extends Controller
{
    // Start a new chat with automatic questions
    public function startChat(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'location' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:15',
                'email' => 'nullable|string|email|max:255',
                'image' => 'nullable|string', // Base64 image
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        // Handle optional image
        $imagePath = null;
        if (!empty($validatedData['image'])) {
            $imagePath = $this->saveBase64Image($validatedData['image']);
        }

        // Create chat with optional fields
        $chat = Chat::create([
            'user_id' => Auth::id(),
            'title' => $validatedData['title'],
            'description' => $validatedData['description'] ?? null,
            'location' => $validatedData['location'] ?? null,
            'phone' => $validatedData['phone'] ?? null,
            'email' => $validatedData['email'] ?? null,
            'image' => $imagePath,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Chat started successfully',
            'chat' => $chat
        ], 201);
    }

    public function updateChat(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'chat_id' => 'required|integer|exists:chats,id',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'location' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:15',
                'email' => 'nullable|string|email|max:255',
                'image' => 'nullable|string', // Base64 string
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        $chat = Chat::find($validatedData['chat_id']);

        if (!$chat) {
            return response()->json(['message' => 'Chat not found'], 404);
        }

        // Restrict update if not admin or owner
        if (!auth()->user()->is_admin && $chat->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        // Save image if present
        if (!empty($validatedData['image'])) {
            $chat->image = $this->saveBase64Image($validatedData['image']);
        }

        // Update fields
        $chat->title = $validatedData['title'] ?? $chat->title;
        $chat->description = $validatedData['description'] ?? $chat->description;
        $chat->location = $validatedData['location'] ?? $chat->location;
        $chat->phone = $validatedData['phone'] ?? $chat->phone;
        $chat->email = $validatedData['email'] ?? $chat->email;
        $chat->save();

        // Check if all required fields are now set
        if (
            $chat->title &&
            $chat->description &&
            $chat->location &&
            $chat->phone &&
            $chat->email &&
            $chat->image
        ) {
            $autoMessage = 'Gracias por tu reporte. Será revisado en breve. Te contactaremos por este medio.';
        
            $alreadySent = ChatMessage::where('chat_id', $chat->id)
                ->where('message', $autoMessage)
                ->where('is_admin', true)
                ->exists();
        
            if (!$alreadySent) {
                // Fetch any admin user
                $admin = \App\Models\User::where('is_admin', 1)->first();
        
                $autoReply = ChatMessage::create([
                    'chat_id' => $chat->id,
                    'user_id' => $chat->user_id,
                    'admin_id' => $admin ? $admin->id : null, // Use admin ID if found
                    'message' => $autoMessage,
                    'is_admin' => true,
                ]);
        
                //broadcast(new \App\Events\MessageSent($autoReply))->toOthers();
            }
        }        

        return response()->json([
            'message' => 'Chat updated successfully',
            'chat' => $chat
        ], 200);
    }

    public function getMessages($chat_id)
    {
        $query = Chat::where('id', $chat_id)
            ->where('created_at', '<=', Carbon::now()->subMinutes(5))
            ->with(['messages' => function ($query) {
                $query->select('id', 'chat_id', 'user_id', 'admin_id', 'message', 'image', 'created_at', 'is_admin')
                    ->orderBy('created_at', 'asc');
            }]);

        $chat = $query->first();

        if ($chat && empty($chat->image) && $chat->messages->isEmpty()) {

            // Get any admin user
            $admin = User::where('is_admin', 1)->first();

            if ($admin) {
                $message = ChatMessage::create([
                    'chat_id' => $chat->id,
                    'admin_id' => $admin->id,
                    'user_id' => $chat->user_id,
                    'message' => 'Gracias por tu reporte. Será revisado en breve. Te contactaremos por este medio.',
                    'is_admin' => true,
                    'image' => null,
                ]);

                //broadcast(new MessageSent($message))->toOthers();
            }
        }

        $query = Chat::where('id', $chat_id)->with(['messages' => function ($query) {
            $query->select('id', 'chat_id', 'user_id', 'admin_id', 'message', 'image', 'created_at', 'is_admin')
                  ->orderBy('created_at', 'asc');
        }]);

        if (!auth()->user()->is_admin) {
            $query->where('user_id', Auth::id());
        }

        $chat = $query->firstOrFail();

        return response()->json([
            'chat' => $chat,
            'messages' => $chat->messages
        ]);
    }

    public function sendMessage(Request $request, $chat_id)
    {
        try {
            $validatedData = $request->validate([
                'message' => 'nullable|string',
                'image' => 'nullable|string',
            ]);

            if (empty($validatedData['message']) && empty($validatedData['image'])) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['message' => ['Either a message or an image is required.']]
                ], 422);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        $query = Chat::where('id', $chat_id);
        if (!auth()->user()->is_admin) {
            $query->where('user_id', Auth::id());
        }
        $chat = $query->firstOrFail();

        $imagePath = null;
        if (!empty($validatedData['image'])) {
            $imagePath = $this->saveBase64Image($validatedData['image']);
        }

        $message = ChatMessage::create([
            'chat_id' => $chat->id,
            'user_id' => Auth::id(),
            'message' => $validatedData['message'] ?? null,
            'image' => $imagePath,
            'is_admin' => false,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message' => 'Message sent successfully',
            'chat' => $message
        ], 201);
    }

    private function saveBase64Image($base64String)
    {
        try {
            if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $matches)) {
                $imageType = $matches[1];
                $base64String = substr($base64String, strpos($base64String, ',') + 1);
                $base64String = base64_decode($base64String);

                if ($base64String === false) {
                    return null;
                }

                $fileName = 'uploads/messages/' . uniqid() . '.' . $imageType;
                Storage::disk('public')->put($fileName, $base64String);

                return str_replace('//storage', '/storage', asset('storage/' . $fileName));
            }
        } catch (\Exception $e) {
            Log::error('Base64 Image Save Error: ' . $e->getMessage());
            return null;
        }
        return null;
    }

    public function adminReply(Request $request, $chat_id)
    {
        try {
            $validatedData = $request->validate([
                'message' => 'nullable|string',
                'image' => 'nullable|string',
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

        broadcast(new MessageSent($message))->toOthers();

        return response()->json(['message' => 'Admin reply sent', 'chat' => $message], 201);
    }

    public function getChatsList()
    {
        $query = Chat::query();
        if (!auth()->user()->is_admin) {
            $query->where('user_id', Auth::id());
        }

        $chats = $query->select('id', 'title', 'location', 'status', 'created_at')
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

        if (!auth()->user()->is_admin) {
            $query->where('user_id', Auth::id());
        }

        if (!empty($validatedData['title'])) {
            $query->where('title', 'LIKE', '%' . $validatedData['title'] . '%');
        }

        if (!empty($validatedData['status'])) {
            $query->where('status', $validatedData['status']);
        }

        $chats = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Chats retrieved successfully',
            'chats' => $chats
        ], 200);
    }
}