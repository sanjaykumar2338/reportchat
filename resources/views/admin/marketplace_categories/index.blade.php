@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3" style="margin-left: 176px; width: 92%;">
        <h2>Gestión de Categorías (Marketplace)</h2>
        <a href="{{ route('admin.marketplace_categories.create') }}" class="btn btn-success">+ Crear Categoría</a>
    </div>

    <form method="GET" action="{{ route('admin.marketplace_categories.index') }}" style="width: 92%; margin-left: 176px;" class="mb-3">
        <div class="row">
            <div class="col-md-4">
                <input type="text" name="name" class="form-control" placeholder="Buscar por Nombre" value="{{ request('name') }}">
            </div>
            <div class="col-md-4">
                <input type="text" name="icon" class="form-control" placeholder="Buscar por Icono" value="{{ request('icon') }}">
            </div>
            <div class="col-md-4 d-flex">
                <button type="submit" class="btn btn-primary me-2">Buscar</button>
                <a href="{{ route('admin.marketplace_categories.index') }}" class="btn btn-secondary">Restablecer</a>
            </div>
        </div>
    </form>

    <table class="table table-bordered" style="width: 92%; margin-left: 176px;">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Icono</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $c)
                <tr>
                    <td>{{ $c->id }}</td>
                    <td>{{ $c->name }}</td>
                    <td>{{ $c->icon }}</td>
                    <td>
                        <a href="{{ route('admin.marketplace_categories.edit', $c->id) }}" class="btn btn-sm btn-warning">Editar</a>
                        <form action="{{ route('admin.marketplace_categories.destroy', $c->id) }}" method="POST" style="display:inline;">
                            @csrf @method('DELETE')
                            <button onclick="return confirm('¿Estás seguro?')" class="btn btn-sm btn-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted"><strong>No se encontraron categorías.</strong></td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-3" style="margin-left: 176px;">
        {{ $categories->links('vendor.pagination.bootstrap-5') }}
    </div>
</div>
@endsection
