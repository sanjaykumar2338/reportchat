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

        return redirect()->route('admin.reservations.index')->with('success', 'Reservation created successfully.');
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
        ]);

        $reservation->update($validated);

        return redirect()->route('admin.reservations.index')->with('success', 'Reservation updated successfully.');
    }

    public function destroy(RoomReservation $reservation)
    {
        $reservation->delete();
        return redirect()->route('admin.reservations.index')->with('success', 'Reservation deleted successfully.');
    }
}
