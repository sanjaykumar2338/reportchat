@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4" style="width: 92%; margin-left: 176px;">
    <h2>Todos los Reportes</h2>

    <!-- Formulario de Búsqueda y Filtro -->
    <form action="{{ route('admin.chats') }}" method="GET" class="mb-3">
        <div class="row">
            <!-- Campo de Búsqueda -->
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Buscar por título..." value="{{ request('search') }}">
            </div>

            <!-- Lista Desplegable de Estado -->
            <div class="col-md-3">
                <select name="status" class="form-control">
                    <option value="">Filtrar por Estado</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                    <option value="solved" {{ request('status') == 'solved' ? 'selected' : '' }}>Resuelto</option>
                    <option value="refused" {{ request('status') == 'refused' ? 'selected' : '' }}>Rechazado</option>
                </select>
            </div>

            <!-- Botón de Búsqueda -->
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Buscar</button>
            </div>

            <!-- Botón de Restablecer -->
            <div class="col-md-2">
                <a href="{{ route('admin.chats') }}" class="btn btn-secondary w-100">Restablecer</a>
            </div>
        </div>
    </form>

    <!-- Tabla de Reportes -->
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Ubicación</th>
                <th>Estado</th>
                <th>Creado el</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @if($chats->count() > 0)
                @foreach($chats as $chat)
                    <tr>
                        <td>{{ $chat->id }}</td>
                        <td>{{ $chat->title }}</td>
                        <td>{{ $chat->location }}</td>
                        <td>
                            <span class="badge bg-{{ 
                                $chat->status == 'solved' ? 'success' : 
                                ($chat->status == 'pending' ? 'warning' : 
                                ($chat->status == 'refused' ? 'danger' : 'secondary')) 
                            }}" id="status-badge-{{ $chat->id }}">
                                {{ ucfirst($chat->status == 'pending' ? 'pendiente' : ($chat->status == 'solved' ? 'resuelto' : ($chat->status == 'refused' ? 'rechazado' : $chat->status))) }}
                            </span>
                        </td>
                        <td>{{ $chat->created_at->format('d M Y, h:i A') }}</td>
                        <td>
                            <a href="{{ route('admin.view.chat', $chat->id) }}" class="btn btn-sm btn-primary">Ver</a>
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        <strong>No se encontraron resultados.</strong>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Paginación -->
    {{ $chats->links('vendor.pagination.bootstrap-5') }}

</div>

@endsection
