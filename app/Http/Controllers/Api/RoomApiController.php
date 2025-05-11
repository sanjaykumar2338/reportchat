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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'duration_minutes' => 'required|integer|in:30,60,90,120',
        ]);

        $start = Carbon::parse("{$validated['date']} {$validated['start_time']}");
        $end = (clone $start)->addMinutes($validated['duration_minutes']);

        // Check conflicts
        $conflict = RoomReservation::where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->where(function($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start->format('H:i'), $end->format('H:i')])
                ->orWhereBetween('end_time', [$start->format('H:i'), $end->format('H:i')]);
            })->exists();

        if ($conflict) {
            return response()->json(['message' => 'Time slot already booked.'], 409);
        }

        $reservation = RoomReservation::create([
            'room_id' => $validated['room_id'],
            'user_id' => auth()->id(),
            'date' => $validated['date'],
            'start_time' => $start->format('H:i'),
            'end_time' => $end->format('H:i'),
            'duration_minutes' => $validated['duration_minutes']
        ]);

        return response()->json(['message' => 'Reservation created.', 'data' => $reservation]);
    }

    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date'
        ]);

        $booked = RoomReservation::where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->get(['start_time', 'end_time']);

        return response()->json(['booked_slots' => $booked]);
    }
}
