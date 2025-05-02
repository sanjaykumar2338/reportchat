<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function userNotifications()
    {
        $userId = Auth::id();

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
            'message' => $deleted ? 'Notification deleted.' : 'Notification not found or not yours.',
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
            'message' => 'All notifications cleared.',
        ]);
    }
}