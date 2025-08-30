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
        @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
        <div class="mb-3">
            <label>Rol</label>
            <select name="role" class="form-control" id="roleSelect">
            <option value="user" {{ old('role', $user->role ?? '')=='user'?'selected':'' }}>Usuario</option>
            @if(auth()->user()->isSuperAdmin())
                <option value="admin" {{ old('role', $user->role ?? '')=='admin'?'selected':'' }}>Administrador</option>
                <option value="superadmin" {{ old('role', $user->role ?? '')=='superadmin'?'selected':'' }}>Superadministrador</option>
            @endif
            </select>
        </div>

        @if(auth()->user()->isSuperAdmin())
            @php
            // Módulos (solo etiqueta en español)
            $modules = [
                'dashboard' => 'Panel de Administración',
                'users' => 'Usuarios',
                'companies' => 'Empresas',
                'rooms' => 'Salas',
                'reservations' => 'Reservas',
                'marketplace_categories' => 'Categorías del Marketplace',
                'marketplace' => 'Anuncios del Marketplace',
                'reports' => 'Reportes'
            ];
            $savedPermissions = old('permissions', $user->permissions ?? []);
            
            // Categorías de Reportes (claves internas => etiqueta visible)
            $reportCategories = [
                'mantenimiento'    => 'Orden de Mantenimiento',
                'limpieza'         => 'Orden de Limpieza',
                'ti'               => 'Servicio de Mantenimiento de TI',
                'quejas_rest'      => 'Quejas y Sugerencias de los Restaurantes',
                'medico'           => 'Servicio Médico',
                'incendio_humo'    => 'Incendio/Humo',
                'seguridad'        => 'Seguridad',
            ];
            $savedReportCats = old('report_categories', $user->report_categories ?? []);
            @endphp

            <div id="permissionsBox" style="{{ old('role', $user->role ?? '')=='admin' ? '' : 'display:none;' }}">
            <label class="mb-1">Permisos de Admin</label>
            @foreach($modules as $key => $label)
                <div class="form-check">
                <input class="form-check-input" type="checkbox" name="permissions[{{ $key }}]" value="1"
                        id="perm_{{ $key }}" {{ !empty($savedPermissions[$key]) ? 'checked' : '' }}>
                <label class="form-check-label" for="perm_{{ $key }}">{{ $label }}</label>
                </div>
            @endforeach

            {{-- Categorías de Reportes (multi-selección) --}}
            <div class="mt-3 p-2 border rounded" id="reportCatsBox"
                style="{{ !empty($savedPermissions['reports']) ? '' : 'display:none;' }}">
                <label class="mb-1 d-block">Categorías visibles en “Reportes”</label>
                @foreach($reportCategories as $key => $label)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                        name="report_categories[]"
                        id="rc_{{ $key }}" value="{{ $key }}"
                        {{ in_array($key, (array)$savedReportCats, true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="rc_{{ $key }}">{{ $label }}</label>
                </div>
                @endforeach
                <small class="text-muted">Si no seleccionas ninguna, el admin no verá ninguna categoría.</small>
            </div>
            </div>
        @endif
        @endif

        <div class="mb-3">
            <button type="submit" class="btn btn-primary">
                {{ isset($user) ? 'Actualizar' : 'Crear' }}
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('roleSelect');
        const permissionsBox = document.getElementById('permissionsBox');
        const permReports = document.getElementById('perm_reports');
        const reportCatsBox = document.getElementById('reportCatsBox');

        function togglePerms() {
            if (!roleSelect) return;
            permissionsBox && (permissionsBox.style.display = (roleSelect.value === 'admin') ? 'block' : 'none');
        }
        function toggleReportCats() {
            if (!permReports || !reportCatsBox) return;
            reportCatsBox.style.display = permReports.checked ? 'block' : 'none';
        }
        roleSelect && roleSelect.addEventListener('change', togglePerms);
        permReports && permReports.addEventListener('change', toggleReportCats);
        togglePerms(); toggleReportCats();
        });
    </script>
@endsection
