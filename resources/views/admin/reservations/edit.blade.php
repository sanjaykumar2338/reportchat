@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3" style="margin-left: 176px; width: 92%;">
        <h2>{{ isset($reservation) ? 'Edit Reservation' : 'Create Reservation' }}</h2>
        <a href="{{ route('admin.reservations.index') }}" class="btn btn-secondary">Back</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" style="margin-left: 176px; width: 92%;">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ isset($reservation) ? route('admin.reservations.update', $reservation->id) : route('admin.reservations.store') }}" method="POST" style="margin-left: 176px; width: 92%;">
        @csrf
        @if(isset($reservation))
            @method('PUT')
        @endif

        <div class="mb-3">
            <label class="form-label">Room</label>
            <select name="room_id" class="form-control" required>
                <option value="">Select Room</option>
                @foreach($rooms as $room)
                    <option value="{{ $room->id }}" {{ (old('room_id', $reservation->room_id ?? '') == $room->id) ? 'selected' : '' }}>
                        {{ $room->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">User</label>
            <select name="user_id" class="form-control" required>
                <option value="">Select User</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ (old('user_id', $reservation->user_id ?? '') == $user->id) ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control" value="{{ old('date', $reservation->date ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Start Time</label>
            <input type="time" name="start_time" class="form-control" value="{{ old('start_time', $reservation->start_time ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">End Time</label>
            <input type="time" name="end_time" class="form-control" value="{{ old('end_time', $reservation->end_time ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Duration (minutes)</label>
            <input type="number" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', $reservation->duration_minutes ?? '') }}" min="1" required>
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-primary">{{ isset($reservation) ? 'Update' : 'Create' }}</button>
        </div>
    </form>
</div>

@endsection
