@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3" style="margin-left: 176px; width: 92%;">
        <h2>{{ isset($reservation) ? 'Editar Reserva' : 'Crear Reserva' }}</h2>
        <a href="{{ route('admin.reservations.index') }}" class="btn btn-secondary">Volver</a>
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
            <label class="form-label">Sala</label>
            <select name="room_id" class="form-control" required>
                <option value="">Seleccionar Sala</option>
                @foreach($rooms as $room)
                    <option value="{{ $room->id }}" {{ (old('room_id', $reservation->room_id ?? '') == $room->id) ? 'selected' : '' }}>
                        {{ $room->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Usuario</label>
            <select name="user_id" class="form-control" required>
                <option value="">Seleccionar Usuario</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ (old('user_id', $reservation->user_id ?? '') == $user->id) ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Fecha</label>
            <input type="date" name="date" class="form-control" value="{{ old('date', $reservation->date ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Hora de Inicio</label>
            <input type="time" name="start_time" class="form-control" value="{{ old('start_time', $reservation->start_time ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Hora de Fin</label>
            <input type="time" name="end_time" class="form-control" value="{{ old('end_time', $reservation->end_time ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Duración</label>
            <input type="number" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', $reservation->duration_minutes ?? '') }}" min="1" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Estado</label>
            <select name="status" class="form-control" required>
                <option value="0" {{ old('status', $reservation->status ?? '') == 0 ? 'selected' : '' }}>Reservada</option>
                <option value="1" {{ old('status', $reservation->status ?? '') == 1 ? 'selected' : '' }}>Cancelada</option>
            </select>
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-primary">{{ isset($reservation) ? 'Actualizar' : 'Crear' }}</button>
        </div>
    </form>
</div>

@endsection
