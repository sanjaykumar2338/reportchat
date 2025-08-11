@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3" style="margin-left: 176px; width: 92%;">
        <h2>{{ isset($user) ? 'Editar Usuario' : 'Crear Usuario' }}</h2>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Volver</a>
    </div>

    {{-- Display all validation errors --}}
    @if ($errors->any())
        <div class="alert alert-danger" style="margin-left: 176px; width: 92%;">
            <strong>Por favor corrige los siguientes errores:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ isset($user) ? route('admin.users.update', $user->id) : route('admin.users.store') }}" method="POST" style="margin-left: 176px; width: 92%;">
        @csrf
        @if(isset($user))
            @method('PUT')
        @endif

        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Correo</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Teléfono</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone ?? '') }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-control" {{ isset($user) ? '' : 'required' }}>
        </div>

        <div class="mb-3">
            <label class="form-label">Empresa</label>
            <select name="company" class="form-control">
                <option value="">Seleccionar Empresa</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" {{ (old('company', $user->company ?? '') == $company->id) ? 'selected' : '' }}>{{ $company->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-primary">{{ isset($user) ? 'Actualizar' : 'Crear' }}</button>
        </div>
    </form>
</div>

@endsection
