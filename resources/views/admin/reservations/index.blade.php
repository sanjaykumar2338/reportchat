@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3" style="margin-left: 176px; width: 92%;">
        <h2>Reservations Management</h2>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3" style="margin-left: 176px; width: 92%;">
    <h2>&nbsp;</h2>
    <div>
        <a href="{{ route('admin.reservations.calendar') }}" class="btn btn-outline-primary me-2">📅 Calendar View</a>
    </div>
</div>

    <!-- Search Form -->
    <form method="GET" action="{{ route('admin.reservations.index') }}" style="width: 92%; margin-left: 176px;" class="mb-3">
        <div class="row">
            <div class="col-md-3">
                <input type="date" name="date" class="form-control" value="{{ request('date') }}">
            </div>
            <div class="col-md-3">
                <select name="room_id" class="form-control">
                    <option value="">All Rooms</option>
                    @foreach(\App\Models\Room::all() as $room)
                        <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>
                            {{ $room->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="user_id" class="form-control" placeholder="User ID" value="{{ request('user_id') }}">
            </div>
            <div class="col-md-3 d-flex">
                <button type="submit" class="btn btn-primary me-2">Search</button>
                <a href="{{ route('admin.reservations.index') }}" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>

    <table class="table table-bordered" style="width: 92%; margin-left: 176px;">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Room</th>
                <th>User</th>
                <th>Date</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Duration (min)</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reservations as $reservation)
                <tr>
                    <td>{{ $reservation->id }}</td>
                    <td>{{ $reservation->room->name ?? 'N/A' }}</td>
                    <td>{{ $reservation->user->name ?? $reservation->user_id }}</td>
                    <td>{{ $reservation->date }}</td>
                    <td>{{ \Carbon\Carbon::parse($reservation->start_time)->format('g:i A') }}</td>
                    <td>{{ \Carbon\Carbon::parse($reservation->end_time)->format('g:i A') }}</td>
                    <td>{{ $reservation->duration_minutes }}</td>
                    <td>
                        @if($reservation->status == 1)
                            <span class="badge bg-danger">Cancelled</span>
                        @else
                            <span class="badge bg-success">Booked</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.reservations.edit', $reservation->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('admin.reservations.destroy', $reservation->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button onclick="return confirm('Are you sure?')" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted"><strong>No reservations found.</strong></td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-3" style="margin-left: 176px;">
        {{ $reservations->links('vendor.pagination.bootstrap-5') }}
    </div>
</div>

@endsection