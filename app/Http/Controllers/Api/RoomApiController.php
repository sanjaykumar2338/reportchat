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
use Illuminate\Support\Facades\Auth;
use Carbon\CarbonPeriod;
use Carbon\Carbon;

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

        $date = $request->get('date', now()->toDateString());

        $rooms = $query->latest()->get()->map(function ($room) use ($date) {
            $reservations = RoomReservation::where('room_id', $room->id)
                ->where('date', $date)
                ->get(['start_time', 'end_time']);

            // Force full 24-hour slot range
            $start = Carbon::createFromTimeString('00:00:00');
            $end = Carbon::createFromTimeString('23:59:00');

            $allSlots = [];

            while ($start->lt($end)) {
                $slotStart = $start->copy();
                $slotEnd = $start->copy()->addMinutes(30);

                if ($slotEnd->gt($end)) {
                    break;
                }

                $isBooked = $reservations->contains(function ($res) use ($slotStart, $slotEnd) {
                    $resStart = Carbon::createFromFormat('H:i:s', $res->start_time);
                    $resEnd = Carbon::createFromFormat('H:i:s', $res->end_time);
                    return $slotStart->lt($resEnd) && $slotEnd->gt($resStart);
                });

                $allSlots[] = [
                    'start_time' => $slotStart->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'is_booked' => $isBooked
                ];

                $start->addMinutes(30);
            }

            $room->image_url = $room->image_url ? asset('storage/' . ltrim($room->image_url, '/')) : null;
            $room->slots = $allSlots;

            return $room;
        });

        return response()->json([
            'status' => 'success',
            'date' => $date,
            'data' => $rooms
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422));
        }

        $data = $validator->validated();

        $start = Carbon::parse("{$data['date']} {$data['start_time']}");
        $end = Carbon::parse("{$data['date']} {$data['end_time']}");

        // Check for conflicts
        $conflict = RoomReservation::where('room_id', $data['room_id'])
            ->where('date', $data['date'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start->format('H:i'), $end->format('H:i')])
                ->orWhereBetween('end_time', [$start->format('H:i'), $end->format('H:i')])
                ->orWhere(function ($q2) use ($start, $end) {
                    $q2->where('start_time', '<', $start->format('H:i'))
                        ->where('end_time', '>', $end->format('H:i'));
                });
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
            'duration_minutes' => $start->diffInMinutes($end),
        ]);

        $reservation->load('room');

        if ($reservation->room) {
            $image = $reservation->room->image_url;
            $reservation->room->image_url = $image && !str_starts_with($image, 'http')
                ? asset('storage/' . ltrim($image, '/'))
                : $image;
        }

        return response()->json([
            'message' => 'Reservation created.',
            'data' => $reservation,
        ]);
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
                if ($reservation->room) {
                    $image = $reservation->room->image_url;
                    if ($image && !str_starts_with($image, 'http')) {
                        $reservation->room->image_url = asset('storage/' . ltrim($image, '/'));
                    } else {
                        $reservation->room->image_url = $image;
                    }
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
