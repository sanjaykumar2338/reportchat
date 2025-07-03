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
use Illuminate\Support\Facades\Log;

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
        if ($request->has('room_id')) {
            $query->where('id', $request->room_id);
        }

        $date = $request->get('date', now()->toDateString());
        $slotDuration = (int) $request->get('slot_duration', 30); // Default to 30 mins
        $filterStartTime = $request->get('start_time'); // Optional start_time

        $rooms = $query->latest()->get()->map(function ($room) use ($date, $slotDuration, $filterStartTime) {
            $reservations = RoomReservation::where('room_id', $room->id)
                ->where('date', $date)
                ->where('status', 0)
                ->get(['start_time', 'end_time']);

            $availableFrom = Carbon::createFromTimeString($room->available_from);
            $availableTo   = Carbon::createFromTimeString($room->available_to);

            // Use provided start_time if it's within range
            $startTime = $availableFrom;
            if ($filterStartTime) {
                try {
                    $parsed = Carbon::createFromFormat('H:i', $filterStartTime);
                    if ($parsed->betweenIncluded($availableFrom, $availableTo)) {
                        $startTime = $parsed;
                    }
                } catch (\Exception $e) {
                    // Invalid format - fallback to default start
                }
            }

            $slots = [];
            $currentTime = $startTime->copy();

            while ($currentTime->lt($availableTo)) {
                $slotStart = $currentTime->copy();
                $slotEnd = $slotStart->copy()->addMinutes($slotDuration);

                if ($slotEnd->gt($availableTo)) break;

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
            $room->load('company');

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
        try {
            // Log all incoming input data
            Log::info('Room reservation request received', [
                'input' => $request->all(),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'timestamp' => now()->toDateTimeString()
            ]);

            $validator = Validator::make($request->all(), [
                'room_id' => 'required|exists:rooms,id',
                'date' => 'required|date|after_or_equal:today',
                'start_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i',
                //'end_time' => 'nullable|date_format:H:i|after:start_time',
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
            $allDay = $request->boolean('all_day');

            if ($allDay) {
                $startTime = '00:00';
                $endTime = '23:59';
            } else {
                $startTime = $data['start_time'] ?? null;
                $endTime = $data['end_time'] ?? null;
            }

            // Validate presence of times
            if (!$startTime || !$endTime) {
                return response()->json(['message' => 'Start and end time are required unless all_day is true.'], 422);
            }

            $start = Carbon::parse($data['date'] . ' ' . $startTime);
            $end = Carbon::parse($data['date'] . ' ' . $endTime);

            // If end is '00:00' or earlier than start, assume next day
            if ($endTime === '00:00' || $end->lt($start)) {
                $end->addDay();
            }

            $weekday = $date->dayOfWeek;
            $repeatOption = $data['repeat_option'] ?? 'none';

            // Conflict check
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

            // Recurring weekly conflict
            $recurringConflict = RoomReservation::where('room_id', $data['room_id'])
                ->where('repeat_option', 'weekly')
                ->where('status', 0)
                ->where('date', '<', $data['date'])
                ->whereTime('start_time', $startTime)
                ->get()
                ->filter(function ($res) use ($weekday) {
                    return Carbon::parse($res->date)->dayOfWeek === $weekday;
                })
                ->count();

            if ($recurringConflict > 0 && !$date->isCurrentWeek()) {
                return response()->json([
                    'message' => 'You can only book this recurring slot for the current week.',
                ], 409);
            }

            // Save reservation
            $reservation = RoomReservation::create([
                'room_id' => $data['room_id'],
                'user_id' => auth()->id(),
                'date' => $data['date'],
                'start_time' => $start->format('H:i'),
                'end_time' => $end->format('H:i'),
                'duration_minutes' => $start->diffInMinutes($end),
                'repeat_option' => $repeatOption,
                'all_day' => $allDay ? 1 : 0,
                'status' => 0,
                'parent_id' => null,
            ]);

            $reservation->load(['room.company']);

            return response()->json([
                'message' => 'Reservation created.',
                'data' => $reservation,
            ]);
        } catch (\Exception $e) {
            Log::error('Room reservation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'input' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
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
            ->where('status', 0)
            ->get(['start_time', 'end_time']);

        return response()->json([
            'status' => 'success',
            'booked_slots' => $booked
        ]);
    } 

    public function profileWithReservations()
    {
        $user = Auth::user();

        $reservations = RoomReservation::with('room.company')->where('user_id', $user->id)
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