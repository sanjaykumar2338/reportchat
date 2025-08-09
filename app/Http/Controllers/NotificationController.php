<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function userNotifications($id = null)
    {
        $userId = Auth::id();

        if ($id) {
            $notification = DB::table('notifications')
                ->where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$notification) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Notificación no encontrada o acceso denegado.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $notification,
            ]);
        }

        // Return all notifications if ID not provided
        $notifications = DB::table('notifications')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $notifications,
        ]);
    }

    // Delete a single notification
    public function deleteNotification($id)
    {
        $userId = Auth::id();

        $deleted = DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json([
            'status' => $deleted ? 'success' : 'error',
            'message' => $deleted ? 'Notificación eliminada.' : 'Notificación no encontrada o no es tuya.',
        ]);
    }

    // Delete all notifications for the user
    public function clearAllNotifications()
    {
        $userId = Auth::id();

        DB::table('notifications')
            ->where('user_id', $userId)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Todas las notificaciones han sido eliminadas.',
        ]);
    }
}
