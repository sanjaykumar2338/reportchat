@extends('layouts.admin')

@section('content')
@include('layouts.sidebar')

<div class="container mt-4" style="margin-left: 176px; width: 92%;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Mi Perfil</h2>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Volver</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Por favor corrige los siguientes errores:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.profile.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="name" class="form-control"
                   value="{{ old('name', $user->name) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Usuario</label>
            <input type="text" name="username" class="form-control"
                   value="{{ old('username', $user->username) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Correo</label>
            <input type="email" name="email" class="form-control"
                   value="{{ old('email', $user->email) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Teléfono</label>
            <input type="text" name="phone" class="form-control"
                   value="{{ old('phone', $user->phone) }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Contraseña (dejar vacío para no cambiar)</label>
            <input type="password" name="password" class="form-control">
        </div>

        {{-- Optional company select; remove if not used --}}
        @isset($companies)
        <div class="mb-3">
            <label class="form-label">Empresa</label>
            <select name="company" class="form-control">
                <option value="">Seleccionar Empresa</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}"
                        {{ (old('company', $user->company_id) == $company->id) ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>
        @endisset

        <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
    </form>
</div>
@endsection
