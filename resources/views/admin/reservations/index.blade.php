@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3" style="margin-left: 176px; width: 92%;">
        <h2>Gestión de Reservas</h2>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3" style="margin-left: 176px; width: 92%;">
        <h2>&nbsp;</h2>
        <div>
            <a href="{{ route('admin.reservations.calendar') }}" class="btn btn-outline-primary me-2">📅 Vista de Calendario</a>
        </div>
    </div>

    <!-- Formulario de Búsqueda -->
    <form method="GET" action="{{ route('admin.reservations.index') }}" style="width: 92%; margin-left: 176px;" class="mb-3">
        <div class="row">
            <div class="col-md-3">
                <input type="date" name="date" class="form-control" value="{{ request('date') }}">
            </div>
            <div class="col-md-3">
                <select name="room_id" class="form-control">
                    <option value="">Todas las Salas</option>
                    @foreach(\App\Models\Room::all() as $room)
                        <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>
                            {{ $room->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="user_id" class="form-control" placeholder="ID de Usuario" value="{{ request('user_id') }}">
            </div>
            <div class="col-md-3 d-flex">
                <button type="submit" class="btn btn-primary me-2">Buscar</button>
                <a href="{{ route('admin.reservations.index') }}" class="btn btn-secondary">Restablecer</a>
            </div>
        </div>
    </form>

    <table class="table table-bordered" style="width: 92%; margin-left: 176px;">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Sala</th>
                <th>Usuario</th>
                <th>Fecha</th>
                <th>Hora de Inicio</th>
                <th>Hora de Fin</th>
                <th>Duración (min)</th>
                <th>Estado</th>
                <th>Acciones</th>
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
                            <span class="badge bg-danger">Cancelada</span>
                        @else
                            <span class="badge bg-success">Reservada</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.reservations.edit', $reservation->id) }}" class="btn btn-sm btn-warning">Editar</a>
                        <form action="{{ route('admin.reservations.destroy', $reservation->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button onclick="return confirm('¿Estás seguro?')" class="btn btn-sm btn-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted"><strong>No se encontraron reservas.</strong></td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-3" style="margin-left: 176px;">
        {{ $reservations->links('vendor.pagination.bootstrap-5') }}
    </div>
</div>

@endsection
