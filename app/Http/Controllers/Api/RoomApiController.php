<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use Illuminate\Support\Facades\Storage;

class RoomApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('floor')) {
            $query->where('floor', 'like', '%' . $request->floor . '%');
        }

        if ($request->has('capacity')) {
            $query->where('capacity', '>=', (int) $request->capacity);
        }

        $rooms = $query->latest()->get()->map(function ($room) {
            $room->image_url = $room->image_url ? asset('storage/' . $room->image_url) : null;
            return $room;
        });

        return response()->json([
            'status' => 'success',
            'data' => $rooms
        ]);
    }
}
