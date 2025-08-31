<?php
namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\User;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Jobs\SendWhatsAppForChat;

class ChatController extends Controller
{
    // Start a new chat with automatic questions
    public function startChat(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',         // e.g., "IT Support Request"
                'sub_type' => 'required|string|max:255',      // e.g., "Software"
                'description' => 'nullable|string',           // Detail of the issue
                'location' => 'nullable|string|max:255',      // e.g., "Building A, 2nd Floor"
                'phone' => 'nullable|string|max:15',
                'email' => 'nullable|string|email|max:255',
                'image' => 'nullable|string',                 // Base64 image
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        // Save image if present
        $imagePath = null;
        if (!empty($validatedData['image'])) {
            $imagePath = $this->saveBase64Image($validatedData['image']);
        }

        // Create chat record
        $chat = Chat::create([
            'user_id' => Auth::id(),
            'title' => $validatedData['title'],
            'sub_type' => $validatedData['sub_type'],
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
                'sub_type' => 'required|string|max:255',
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
        if ((int)auth()->user()->is_admin === 0 && (int)$chat->user_id !== (int)Auth::id()) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        // Save image if present
        if (!empty($validatedData['image'])) {
            $chat->image = $this->saveBase64Image($validatedData['image']);
        }

        // Update fields
        $chat->title = $validatedData['title'] ?? $chat->title;
        $chat->sub_type = $validatedData['sub_type'] ?? $chat->sub_type;
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

                SendWhatsAppForChat::dispatch($chat->id);
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
            ->where('created_at', '<=', Carbon::now()->subMinutes(30))
            ->with(['messages' => function ($query) {
                $query->select('id', 'chat_id', 'user_id', 'admin_id', 'message', 'image', 'created_at', 'is_admin')
                    ->orderBy('created_at', 'asc');
            }]);

        $chat = $query->first();

        if (
            $chat &&
            empty($chat->image) &&
            $chat->messages->isEmpty() &&
            $chat->title &&
            $chat->description &&
            $chat->location &&
            $chat->phone &&
            $chat->email
        ) {
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

                broadcast(new MessageSent($message))->toOthers();
            }
        }

        $query = Chat::where('id', $chat_id)
            ->with(['messages' => function ($q) {
                $q->select('id', 'chat_id', 'user_id', 'admin_id', 'message', 'image', 'created_at', 'is_admin', 'is_read')
                ->orderBy('created_at', 'asc');
            }]);

        $chat = $query->first();

        // ✅ Update all unread messages to read after fetching
        if ($chat) {
            $chat->messages()
                ->where('is_read', 0)
                ->update(['is_read' => 1]);
        }

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

        $hasMessages = ChatMessage::where('chat_id', $chat->id)->exists();

        $message = ChatMessage::create([
            'chat_id' => $chat->id,
            'user_id' => $chat->user_id,
            'message' => $validatedData['message'] ?? null,
            'image' => $imagePath,
            'is_admin' => false,
        ]);

        // Send system message if no previous messages and chat info is complete
        $requiredFieldsFilled = $chat->title && $chat->description && $chat->location && $chat->phone && $chat->email;
        if (!$hasMessages && $requiredFieldsFilled) {
            $adminUser = \App\Models\User::where('is_admin', 1)->first();

            ChatMessage::create([
                'chat_id' => $chat->id,
                'user_id' => $chat->user_id,
                'admin_id' => $adminUser?->id ?? null,
                'message' => 'Gracias por tu reporte. Será revisado en breve. Te contactaremos por este medio.',
                'image' => null,
                'is_admin' => true,
            ]);
        }

        //broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message' => 'Message sent successfully',
            'chat' => $message
        ], 201);
    }

    private function saveBase64Image($base64String)
    {
        try {
            $imageData = $base64String;
            $imageType = null;

            // 1. Check if the string has the data URI scheme prefix
            if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $matches)) {
                $imageType = strtolower($matches[1]);
                $imageData = substr($base64String, strpos($base64String, ',') + 1);
            }

            // 2. Decode the base64 data
            $decodedImage = base64_decode($imageData);
            if ($decodedImage === false) {
                Log::error('Base64 Decode Error: Failed to decode the image string.');
                return null;
            }

            // 3. If the image type was not in the prefix, detect it from the binary data
            if ($imageType === null) {
                $finfo = finfo_open();
                $mime_type = finfo_buffer($finfo, $decodedImage, FILEINFO_MIME_TYPE);
                finfo_close($finfo);
                $imageType = substr($mime_type, strpos($mime_type, '/') + 1);
            }

            // 4. Check if we have a valid image type
            $allowedTypes = ['jpeg', 'jpg', 'png', 'gif'];
            if (!in_array($imageType, $allowedTypes)) {
                Log::error('Invalid Image Type: Detected type was ' . $imageType);
                return null;
            }

            // 5. Create a unique filename and save the file
            $fileName = 'uploads/messages/' . uniqid() . '.' . $imageType;
            Storage::disk('public')->put($fileName, $decodedImage);

            // 6. Return the FULL public URL for the saved image using the asset() helper
            //    This is the only line that has changed.
            return asset(Storage::url($fileName));

        } catch (\Exception $e) {
            Log::error('Base64 Image Save Exception: ' . $e->getMessage());
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

    public function getChatsList(Request $request)
    {
        $query = Chat::query();

        if (!auth()->user()->is_admin) {
            $query->where('user_id', Auth::id());
        }

        if ($request->has('category') && $request->category !== null) {
            $query->where('title', $request->category);
        }

        $chats = $query->select('id', 'title', 'location', 'sub_type', 'status', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->get();

        $formattedChats = $chats->map(function ($chat) {
            return [
                'id' => $chat->id,
                'title' => $chat->title,
                'location' => $chat->location,
                'sub_type' => $chat->sub_type,
                'status' => $chat->status,
                'created_at' => $chat->created_at,
                'unread_count' => ChatMessage::where('chat_id', $chat->id)->where('is_read', 0)->count()
            ];
        });

        return response()->json([
            'message' => 'Chats retrieved successfully',
            'chats' => $formattedChats
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
