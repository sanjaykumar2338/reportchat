<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomReservation;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $query = RoomReservation::with(['room', 'user']);

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $reservations = $query->latest()->paginate(10)->withQueryString();

        return view('admin.reservations.index', compact('reservations'));
    }

    public function create()
    {
        $rooms = Room::all();
        return view('admin.reservations.create', compact('rooms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'duration_minutes' => 'required|integer|min:1',
        ]);

        RoomReservation::create($validated);

        return redirect()->route('admin.reservations.index')
            ->with('success', 'Reserva creada exitosamente.');
    }

    public function edit(RoomReservation $reservation)
    {
        $rooms = Room::all();
        $users = \App\Models\User::all();

        return view('admin.reservations.edit', compact('reservation', 'rooms', 'users'));
    }

    public function update(Request $request, RoomReservation $reservation)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'start_time' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'end_time' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/', 'after:start_time'],
            'duration_minutes' => 'required|integer|min:1',
            'status' => 'required|in:0,1',
        ]);

        $reservation->update($validated);

        return redirect()->route('admin.reservations.index')
            ->with('success', 'Reserva actualizada exitosamente.');
    }

    public function destroy(RoomReservation $reservation)
    {
        $reservation->update([
            'status' => 1, // 1 = Cancelada, 0 = Activa
        ]);

        return redirect()->route('admin.reservations.index')
            ->with('success', 'Reserva cancelada exitosamente.');
    }

    public function calendar(Request $request)
    {
        $users = \App\Models\User::all();
        $rooms = \App\Models\Room::all();
        return view('admin.reservations.calendar', [
            'users' => $users,
            'rooms' => $rooms,
            'filters' => $request->only(['user_id', 'room_id']),
        ]);
    }

    public function calendarEvents(Request $request)
    {
        $query = RoomReservation::with(['room', 'user']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        $reservations = $query->get();

        $events = $reservations->map(function ($res) {
            return [
                'title' => $res->room->name . ' - ' . $res->user->name,
                'start' => $res->date . 'T' . $res->start_time,
                'end' => $res->date . 'T' . $res->end_time,
                'url' => route('admin.reservations.edit', $res->id),
            ];
        });

        return response()->json($events);
    }
}