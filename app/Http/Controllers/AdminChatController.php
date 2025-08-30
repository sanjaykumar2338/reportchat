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
        $user  = auth()->user();
        $query = Chat::query();

        // 🔒 If ADMIN: limit by assigned report categories (titles)
        if ($user->isAdmin() && !$user->isSuperAdmin()) {
            // Keys saved on the user → labels stored in `chats.title`
            $labelsMap = [
                'mantenimiento' => 'Orden de Mantenimiento',
                'limpieza'      => 'Orden de Limpieza',
                'ti'            => 'Servicio de Mantenimiento de TI',
                'quejas_rest'   => 'Quejas y Sugerencias de los Restaurantes',
                'medico'        => 'Servicio Médico',
                'incendio_humo' => 'Incendio/Humo',
                'seguridad'     => 'Seguridad',
            ];

            $allowedKeys = (array) ($user->report_categories ?? []);

            // map keys → titles
            $allowedTitles = [];
            foreach ($allowedKeys as $k) {
                if (isset($labelsMap[$k])) {
                    $allowedTitles[] = $labelsMap[$k];
                }
            }

            // If no categories assigned → show none (or abort 403 if you prefer)
            if (empty($allowedTitles)) {
                // Option A: show nothing
                $query->whereRaw('1=0');
                // Option B: abort
                // abort(403, 'No tienes permisos para ver reportes.');
            } else {
                $query->whereIn('title', $allowedTitles);
            }
        }

        // 🔎 Search
        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        // 🏷️ Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 📄 Pagination
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

        return redirect()->route('admin.view.chat', $chat_id)->with('success', 'Estado actualizado correctamente.');
    }

    public function updateStatus(Request $request, $chat_id)
    {
        $request->validate([
            'status' => 'required|in:pending,refused,solved',
        ]);

        $chat = Chat::findOrFail($chat_id);
        $chat->status = $request->status;
        $chat->save();

        return response()->json(['success' => true, 'message' => 'Estado actualizado correctamente.']);
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
                            "title" => "Nuevo mensaje del administrador",
                            "body" => "Tienes un nuevo mensaje en el chat #{$chat_id}",
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
            'message' => 'Mensaje enviado y notificación enviada correctamente',
            'chat_message' => $chatMessage,
        ], 201);
    }
}
