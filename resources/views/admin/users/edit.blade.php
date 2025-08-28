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

    <form action="{{ isset($user) ? route('admin.users.update', $user->id) : route('admin.users.store') }}" 
          method="POST" style="margin-left: 176px; width: 92%;">
        @csrf
        @if(isset($user))
            @method('PUT')
        @endif

        {{-- Nombre --}}
        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="name" class="form-control" 
                   value="{{ old('name', $user->name ?? '') }}" required>
        </div>

        {{-- Usuario --}}
        <div class="mb-3">
            <label class="form-label">Usuario</label>
            <input type="text" name="username" class="form-control" 
                   value="{{ old('username', $user->username ?? '') }}" required>
        </div>

        {{-- Correo --}}
        <div class="mb-3">
            <label class="form-label">Correo</label>
            <input type="email" name="email" class="form-control" 
                   value="{{ old('email', $user->email ?? '') }}" required>
        </div>

        {{-- Teléfono --}}
        <div class="mb-3">
            <label class="form-label">Teléfono</label>
            <input type="text" name="phone" class="form-control" 
                   value="{{ old('phone', $user->phone ?? '') }}">
        </div>

        {{-- Contraseña --}}
        <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-control" 
                   {{ isset($user) ? '' : 'required' }}>
        </div>

        {{-- Empresa --}}
        <div class="mb-3">
            <label class="form-label">Empresa</label>
            <select name="company" class="form-control">
                <option value="">Seleccionar Empresa</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" 
                        {{ (old('company', $user->company ?? '') == $company->id) ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Role & Permissions --}}
        @if(auth()->user()->isSuperAdmin())
            <div class="mb-3">
                <label>Rol</label>
                <select name="role" class="form-control" id="roleSelect">
                    <option value="user" {{ old('role', $user->role ?? '')=='user'?'selected':'' }}>Usuario</option>
                    <option value="admin" {{ old('role', $user->role ?? '')=='admin'?'selected':'' }}>Admin</option>
                    <option value="superadmin" {{ old('role', $user->role ?? '')=='superadmin'?'selected':'' }}>Superadmin</option>
                </select>
            </div>

            <div id="permissionsBox" style="{{ old('role', $user->role ?? '')=='admin' ? '' : 'display:none;' }}">
                <label>Permisos de Admin</label>
                @php
                    $modules = ['dashboard','users','companies','rooms','reservations','marketplace_categories','marketplace','reports'];
                    $savedPermissions = old('permissions', $user->permissions ?? []);
                @endphp
                @foreach($modules as $module)
                    <div>
                        <input type="checkbox" name="permissions[{{ $module }}]" value="1"
                               {{ !empty($savedPermissions[$module]) ? 'checked' : '' }}>
                        {{ ucfirst($module) }}
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Submit --}}
        <div class="mb-3">
            <button type="submit" class="btn btn-primary">{{ isset($user) ? 'Actualizar' : 'Crear' }}</button>
        </div>
    </form>
</div>

{{-- ✅ Add script to toggle permissions dynamically --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    let roleSelect = document.getElementById('roleSelect');
    let permissionsBox = document.getElementById('permissionsBox');

    if(roleSelect){
        roleSelect.addEventListener('change', function () {
            if (this.value === 'admin') {
                permissionsBox.style.display = 'block';
            } else {
                permissionsBox.style.display = 'none';
            }
        });
    }
});
</script>

@endsection
