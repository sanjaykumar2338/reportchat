<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\Company;
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

        // Optional filters
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
        if ($request->has('company')) {
            $query->where('company', $request->company);
        }


        $date = $request->get('date', now()->toDateString());
        $slotDuration = (int) $request->get('slot_duration', 30); // Default to 30 mins

        $rooms = $query->latest()->get()->map(function ($room) use ($date, $slotDuration) {
            // Get reservations for this room on the given date
            $reservations = RoomReservation::where('room_id', $room->id)
                ->where('date', $date)
                ->get(['start_time', 'end_time']);

            // Use room's available time range
            $startOfDay = Carbon::createFromTimeString($room->available_from);
            $endOfDay = Carbon::createFromTimeString($room->available_to);

            $slots = [];
            $currentTime = $startOfDay->copy();

            while ($currentTime->lt($endOfDay)) {
                $slotStart = $currentTime->copy();
                $slotEnd = $currentTime->copy()->addMinutes($slotDuration);

                if ($slotEnd->gt($endOfDay)) {
                    break;
                }

                // Check for reservation overlap
                $isBooked = $reservations->contains(function ($res) use ($slotStart, $slotEnd) {
                    $resStart = Carbon::parse($res->start_time);
                    $resEnd = Carbon::parse($res->end_time);

                    return $slotStart->lt($resEnd) && $slotEnd->gt($resStart);
                });

                $slots[] = [
                    'start_time' => $slotStart->format('H:i'),
                    'end_time'   => $slotEnd->format('H:i'),
                    'is_booked'  => $isBooked,
                ];

                $currentTime->addMinutes($slotDuration);
            }

            $room->image_url = $room->image_url ? asset('storage/' . ltrim($room->image_url, '/')) : null;
            $room->slots = $slots;

            return $room;
        });

        return response()->json([
            'status' => 'success',
            'date'   => $date,
            'data'   => $rooms
        ]);
    }

    public function companylist(){
        return response()->json([
            'status' => 'success',
            'companies' => Company::get()
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required_if:all_day,false|date_format:H:i',
            'end_time' => 'required_if:all_day,false|date_format:H:i|after:start_time',
            'repeat_option' => 'nullable|in:none,weekly',
            'all_day' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422));
        }

        $data = $validator->validated();
        $date = Carbon::parse($data['date']);
        $start = Carbon::parse($data['date'] . ' ' . ($request->all_day ? '00:00' : $data['start_time']));
        $end = Carbon::parse($data['date'] . ' ' . ($request->all_day ? '23:59' : $data['end_time']));
        $startTime = $start->format('H:i');
        $endTime = $end->format('H:i');
        $repeatOption = $data['repeat_option'] ?? 'none';
        $weekday = $date->dayOfWeek;

        // ✅ 1. Standard conflict check on this date
        $conflict = RoomReservation::where('room_id', $data['room_id'])
            ->where('date', $data['date'])
            ->where('status', 0)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                ->orWhereBetween('end_time', [$startTime, $endTime])
                ->orWhere(function ($q2) use ($startTime, $endTime) {
                    $q2->where('start_time', '<', $startTime)
                        ->where('end_time', '>', $endTime);
                });
            })
            ->exists();

        if ($conflict) {
            return response()->json(['message' => 'Time slot already booked for this date.'], 409);
        }

        // ✅ 2. Check if a future recurring reservation blocks this pattern
        $recurringConflict = RoomReservation::where('room_id', $data['room_id'])
            ->where('repeat_option', 'weekly')
            ->where('status', 0)
            ->where('date', '<', $data['date']) // Existing weekly reservation created earlier
            ->whereTime('start_time', $startTime)
            ->get()
            ->filter(function ($res) use ($weekday) {
                return Carbon::parse($res->date)->dayOfWeek === $weekday;
            })
            ->count();

        if ($recurringConflict > 0) {
            return response()->json([
                'message' => 'This weekly recurring slot is already booked for future weeks. Only this week is allowed.',
            ], 409);
        }

        // ✅ 3. Allow booking only for current week
        if (!$date->isCurrentWeek() && $recurringConflict > 0) {
            return response()->json([
                'message' => 'You can only book this recurring slot for the current week.',
            ], 409);
        }

        // ✅ 4. Passed — Save the reservation
        $reservation = RoomReservation::create([
            'room_id' => $data['room_id'],
            'user_id' => auth()->id(),
            'date' => $data['date'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => $start->diffInMinutes($end),
            'repeat_option' => $repeatOption,
            'all_day' => $request->all_day ? 1 : 0,
            'status' => 0,
            'parent_id' => null,
        ]);

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
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|exists:room_reservations,id',
            'type' => 'required|in:this,this_and_following',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $reservation = RoomReservation::where('id', $request->reservation_id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$reservation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reservation not found or unauthorized.',
            ], 404);
        }

        // If it's not a recurring reservation, cancel it directly
        if ($reservation->repeat_option === 'none' || $request->type === 'this') {
            $reservation->update(['status' => 1]);

            return response()->json([
                'status' => 'success',
                'message' => 'Only this reservation was cancelled.',
            ]);
        }

        // Otherwise, cancel this and all future reservations in the recurring series
        $baseId = $reservation->parent_id ?? $reservation->id;

        RoomReservation::where(function ($query) use ($baseId) {
                $query->where('parent_id', $baseId)
                    ->orWhere('id', $baseId);
            })
            ->where('date', '>=', $reservation->date)
            ->where('status', 0)
            ->update(['status' => 1]);

        return response()->json([
            'status' => 'success',
            'message' => 'This and all following reservations were cancelled.',
        ]);
    }
}