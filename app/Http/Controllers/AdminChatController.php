<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\Request;

class AdminChatController extends Controller
{
    public function index()
    {
        $chats = Chat::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.chats.index', compact('chats'));
    }

    public function viewChat($chat_id)
    {
        $chat = Chat::with('messages')->findOrFail($chat_id);
        return view('admin.chats.view', compact('chat'));
    }

    public function sendMessage11(Request $request, $chat_id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $chat = Chat::findOrFail($chat_id);
        $chat->messages()->create([
            'admin_id' => auth()->id(),
            'message' => $request->message,
            'is_admin' => true,
        ]);

        return redirect()->route('admin.view.chat', $chat_id)->with('success', 'Message sent successfully.');
    }

    public function updateStatus(Request $request, $chat_id)
    {
        $request->validate([
            'status' => 'required|in:open,pending,refused,closed',
        ]);

        $chat = Chat::findOrFail($chat_id);
        $chat->status = $request->status;
        $chat->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    public function fetchMessages($chat_id)
    {
        $chat = Chat::with(['messages.user']) // Include user details
            ->findOrFail($chat_id);

        return response()->json([
            'success' => true,
            'messages' => $chat->messages
        ]);
    }

    public function sendMessage(Request $request, $chat_id)
    {
        // Validate request
        $validatedData = $request->validate([
            'message' => 'required|string',
        ]);

        // Find chat
        $chat = Chat::findOrFail($chat_id);
        $chat->messages()->create([
            'admin_id' => auth()->id(),
            'message' => $request->message,
            'is_admin' => true,
        ]);

        // Return JSON response
        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'chat_message' => $chat,
        ], 201);
    }
}
