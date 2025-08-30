@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3" style="margin-left: 176px; width: 92%;">
        <h2>Gestión de Usuarios</h2>
        <div class="d-flex justify-content-between align-items-center mb-3" style="margin-left: 176px; width: 92%;">
            <h2>Gestión de Usuarios</h2>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.users.uploadCsv') }}" class="btn btn-primary">Subir CSV</a>
                <a href="{{ route('admin.users.create') }}" class="btn btn-success">+ Crear Usuario</a>
            </div>
        </div>
    </div>

    <!-- Formulario de Búsqueda -->
    <form method="GET" action="{{ route('admin.users.index') }}" style="width: 92%; margin-left: 176px;" class="mb-3">
        <div class="row">
            <div class="col-md-3">
                <input type="text" name="name" class="form-control" placeholder="Buscar por Nombre" value="{{ request('name') }}">
            </div>
            <div class="col-md-3">
                <input type="email" name="email" class="form-control" placeholder="Buscar por Correo" value="{{ request('email') }}">
            </div>
            <div class="col-md-6 d-flex">
                <button type="submit" class="btn btn-primary me-2">Buscar</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Restablecer</a>
            </div>
        </div>
    </form>

    <table class="table table-bordered" style="width: 92%; margin-left: 176px;">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Empresa</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->phone }}</td>
                    <td>{{ $user->companyRelation->name ?? '-' }}</td>
                    <td>
                        <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-sm btn-info">Ver</a>
                        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-warning">Editar</a>
                        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button onclick="return confirm('¿Estás seguro?')" class="btn btn-sm btn-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted"><strong>No se encontraron usuarios.</strong></td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Paginación -->
    <div class="mt-3" style="margin-left: 176px;">
        {{ $users->links('vendor.pagination.bootstrap-5') }}
    </div>
</div>

@endsection
