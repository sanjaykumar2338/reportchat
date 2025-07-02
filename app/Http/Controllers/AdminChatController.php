<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;

class AdminChatController extends Controller
{
    public function index(Request $request)
    {
        $query = Chat::query();
    
        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
    
        // Apply status filter
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }
    
        // Fetch chats with pagination
        $chats = $query->orderBy('created_at', 'desc')->paginate(10);
    
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
            'status' => 'required|in:pending,refused,solved',
        ]);

        $chat = Chat::findOrFail($chat_id);
        $chat->status = $request->status;
        $chat->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    public function fetchMessages($chat_id)
    {
        $chat = Chat::with([
            'messages' => function ($q) {
                $q->orderBy('created_at')->with('user');
            },
            'user'
        ])->findOrFail($chat_id);

        $initialQuestionMap = [
            'title' => 'Selecciona una opción',
            'sub_type' => 'Selecciona el tipo de problema.',
            'location' => 'Específica el lugar (Empresa, Piso, referencias, etc..)',
            'description' => 'Describe a detalle lo que quieres reportar',
        ];

        $virtualMessages = [];
        $user = $chat->user;

        foreach ($initialQuestionMap as $field => $question) {
            if (!empty($chat->$field)) {
                $virtualMessages[] = [
                    'id' => "virtual_{$field}_question",
                    'chat_id' => $chat->id,
                    'message' => $question,
                    'is_admin' => true,
                    'created_at' => $chat->created_at,
                ];

                $virtualMessages[] = [
                    'id' => "virtual_{$field}_answer",
                    'chat_id' => $chat->id,
                    'message' => $chat->$field,
                    'user_id' => $chat->user_id,
                    'is_admin' => false,
                    'created_at' => $chat->created_at,
                    'user' => $user,
                ];
            }
        }

        $chatMessages = $chat->messages->map(function ($msg) {
            return [
                'id' => $msg->id,
                'chat_id' => $msg->chat_id,
                'user_id' => $msg->user_id,
                'admin_id' => $msg->admin_id,
                'message' => $msg->message,
                'image' => $msg->image,
                'is_admin' => (bool)$msg->is_admin,
                'is_read' => $msg->is_read,
                'created_at' => $msg->created_at,
                'updated_at' => $msg->updated_at,
                'user' => $msg->user,
            ];
        });

        // Inject "Envíanos una o más imágenes" and "Gracias por tu reporte" correctly
        $finalMessages = $virtualMessages;
        $insertedGracias = false;

        foreach ($chatMessages as $index => $message) {
            // If first user message is image/text, prepend the prompt
            if ($index === 0 && !$message['is_admin'] && (!empty($message['message']) || !empty($message['image']))) {
                $finalMessages[] = [
                    'id' => "virtual_image_prompt",
                    'chat_id' => $chat->id,
                    'message' => 'Envíanos una o más imágenes',
                    'is_admin' => true,
                    'created_at' => $message['created_at'],
                ];
            }

            $finalMessages[] = $message;

            // After first non-admin message with text or image, insert thank-you
            if (!$insertedGracias && !$message['is_admin'] && (!empty($message['message']) || !empty($message['image']))) {
                $finalMessages[] = [
                    'id' => "virtual_thank_you",
                    'chat_id' => $chat->id,
                    'message' => 'Gracias por tu reporte. Será revisado en breve. Te contactaremos por este medio.',
                    'is_admin' => true,
                    'created_at' => $message['created_at'],
                ];
                $insertedGracias = true;
            }
        }

        return response()->json([
            'success' => true,
            'chat_id' => $chat->id,
            'messages' => $finalMessages,
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

        // Save chat message
        $chatMessage = $chat->messages()->create([
            'admin_id' => auth()->id(),
            'message' => $request->message,
            'is_admin' => true,
        ]);

        // Get the user of this chat
        $user = User::find($chat->user_id);

        if ($user && $user->fcm_token) {
            // Step 1: Generate access token
            $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
            $serviceAccountPath = storage_path('app/firebase/firebase-credentials.json');
            $credentials = new ServiceAccountCredentials($scopes, $serviceAccountPath);
            $tokenData = $credentials->fetchAuthToken();

            if (isset($tokenData['access_token'])) {
                $accessToken = $tokenData['access_token'];
                $projectId = json_decode(file_get_contents($serviceAccountPath), true)['project_id'];

                // Step 2: Prepare notification payload
                $notificationData = [
                    "message" => [
                        "token" => $user->fcm_token,
                        "notification" => [
                            "title" => "New Message from Admin",
                            "body" => "Message in Chat #{$chat_id}",
                        ],
                        "data" => [
                            "chat_id" => (string) $chat_id,
                            "type" => "chat_message"
                        ]
                    ]
                ];

                // Step 3: Send notification
                try {
                    $apiurl = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $apiurl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notificationData));
                    $headers = [
                        'Authorization: Bearer ' . $accessToken,
                        'Content-Type: application/json'
                    ];
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    $result = curl_exec($ch);
                    \Log::info('FCM Response', ['chat_id' => $chat_id, 'res' => $result]);
                } catch (\Exception $e) {
                    \Log::error("FCM send failed for user {$user->id}: " . $e->getMessage());
                }
            } else {
                \Log::error("FCM access token generation failed.");
            }
        }

        // Return JSON response
        return response()->json([
            'success' => true,
            'message' => 'Message sent and notification triggered',
            'chat_message' => $chatMessage,
        ], 201);
    }
}
