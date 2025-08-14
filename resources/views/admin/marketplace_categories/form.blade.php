@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4" style="margin-left: 176px; width: 92%;">
    @php $editing = isset($category); @endphp
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>{{ $editing ? 'Editar Categoría' : 'Crear Categoría' }}</h2>
        <a href="{{ route('admin.marketplace_categories.index') }}" class="btn btn-secondary">Volver</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $editing
        ? route('admin.marketplace_categories.update', $category->id)
        : route('admin.marketplace_categories.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="name" class="form-control"
                   value="{{ old('name', $category->name ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Icono (archivo o texto)</label>
            <input type="text" name="icon" class="form-control"
                   value="{{ old('icon', $category->icon ?? '') }}" placeholder="ej: product-icon.png">
            <small class="text-muted">Puedes guardar solo el nombre del icono o la ruta si ya existe.</small>
        </div>

        <button class="btn btn-primary">{{ $editing ? 'Actualizar' : 'Crear' }}</button>
    </form>
</div>
@endsection
