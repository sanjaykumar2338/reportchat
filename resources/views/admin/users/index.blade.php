@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3" style="margin-left: 176px; width: 92%;">
        <h2>Gestión de Usuarios</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.users.uploadCsv') }}" class="btn btn-primary">Subir CSV</a>
            <a href="{{ route('admin.users.create') }}" class="btn btn-success">+ Crear Usuario</a>
            
            {{-- ✅ CHANGE 1: Only show this button to super-admins --}}
            @if (Auth::check() && Auth::user()->role === 'superadmin')
                <form action="{{ route('admin.users.toggleAllNotifications') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn {{ $allEnabled ? 'btn-danger' : 'btn-warning' }}">
                        {{ $allEnabled ? 'Deshabilitar Notificaciones (Todos)' : 'Habilitar Notificaciones (Todos)' }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" style="width: 92%; margin-left: 176px;" class="mb-3">
        {{-- ... your search form ... --}}
    </form>

    <table class="table table-bordered" style="width: 92%; margin-left: 176px;">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Empresa</th>
                <th>Rol</th>
                {{-- ✅ CHANGE 2: Only show this table header to super-admins --}}
                @if (Auth::check() && Auth::user()->role === 'super-admin')
                    <th>Notif. WhatsApp</th>
                @endif
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
                    <td><strong>{{ ucfirst($user->role) }}</strong></td>
                    
                    {{-- ✅ CHANGE 3: Only show this table cell to super-admins --}}
                    @if (Auth::check() && Auth::user()->role === 'superadmin')
                        <td class="text-center">
                            {{-- Conditionally show checkbox ONLY for admin-level users --}}
                            @if ($user->role === 'admin')
                                <form action="{{ route('admin.users.toggleNotification', $user->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="checkbox" name="whatsapp_notifications" value="1"
                                        onchange="this.form.submit()" {{ $user->whatsapp_notifications ? 'checked' : '' }}>
                                </form>
                            @else
                                - {{-- Show a dash for non-admins --}}
                            @endif
                        </td>
                    @endif

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
                    {{-- ✅ CHANGE 4: Adjust colspan based on if the column is visible --}}
                    @if (Auth::check() && Auth::user()->role === 'super-admin')
                        <td colspan="8" class="text-center text-muted"><strong>No se encontraron usuarios.</strong></td>
                    @else
                        <td colspan="7" class="text-center text-muted"><strong>No se encontraron usuarios.</strong></td>
                    @endif
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-3" style="margin-left: 176px;">
        {{ $users->links('vendor.pagination.bootstrap-5') }}
    </div>
</div>

@endsection