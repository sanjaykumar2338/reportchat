<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\RoomReservation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'duration_minutes' => 'required|integer|in:30,60,90,120',
        ]);

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422));
        }

        $data = $validator->validated();

        $start = Carbon::parse("{$data['date']} {$data['start_time']}");
        $end = (clone $start)->addMinutes((int) $data['duration_minutes']);


        // Check for conflicts
        $conflict = RoomReservation::where('room_id', $data['room_id'])
            ->where('date', $data['date'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start->format('H:i'), $end->format('H:i')])
                ->orWhereBetween('end_time', [$start->format('H:i'), $end->format('H:i')]);
            })->exists();

        if ($conflict) {
            return response()->json(['message' => 'Time slot already booked.'], 409);
        }

        $reservation = RoomReservation::create([
            'room_id' => $data['room_id'],
            'user_id' => auth()->id(),
            'date' => $data['date'],
            'start_time' => $start->format('H:i'),
            'end_time' => $end->format('H:i'),
            'duration_minutes' => $data['duration_minutes'],
        ]);

        return response()->json(['message' => 'Reservation created.', 'data' => $reservation]);
    }

    public function checkAvailability(Request $request)
    {
        // Normalize the date input to Y-m-d format
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date'
        ]);

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422));
        }

        $data = $validator->validated();

        $booked = RoomReservation::where('room_id', $data['room_id'])
            ->where('date', $data['date'])
            ->get(['start_time', 'end_time']);

        return response()->json([
            'status' => 'success',
            'booked_slots' => $booked
        ]);
    }

    public function profileWithReservations()
    {
        $user = Auth::user();

        $reservations = RoomReservation::with('room')->where('user_id', $user->id)
            ->orderBy('date')
            ->get()
            ->map(function ($reservation) {
                if ($reservation->room && $reservation->room->image_url) {
                    $reservation->room->image_url = asset('storage/' . $reservation->room->image_url);
                } else {
                    $reservation->room->image_url = null;
                }
                return $reservation;
            });

        return response()->json([
            'status' => 'success',
            'reservations' => $reservations
        ]);
    }

    public function cancelReservation(Request $request)
    {
        $reservationId = $request->query('reservation_id');

        if (!$reservationId || !is_numeric($reservationId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or missing reservation_id'
            ], 422);
        }

        $reservation = RoomReservation::where('id', $reservationId)
            ->where('user_id', auth()->id()) // optional: restrict to logged-in user's own reservation
            ->first();

        if (!$reservation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reservation not found or not authorized.'
            ], 404);
        }

        $reservation->status = 1; // 1 means "cancelled"
        $reservation->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Reservation cancelled successfully.'
        ]);
    }
}
