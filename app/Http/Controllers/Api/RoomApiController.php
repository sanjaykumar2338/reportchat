<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;

class RoomApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('floor')) {
            $query->where('floor', 'like', '%'.$request->floor.'%');
        }

        if ($request->has('capacity')) {
            $query->where('capacity', '>=', (int)$request->capacity);
        }

        $rooms = $query->latest()->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $rooms
        ]);
    }
}
