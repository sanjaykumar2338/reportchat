<style>
    body { background-color: #f8f9fa; }
    .sidebar {
        height: 100vh; width: 250px; position: fixed; top: 0; left: 0;
        background-color: #343a40; padding-top: 20px;
    }
    .sidebar a {
        padding: 12px 15px; text-decoration: none; font-size: 16px; color: white;
        display: block; transition: 0.3s; border-left: 3px solid transparent;
    }
    .sidebar a:hover, .sidebar a.active { background-color: #495057; border-left: 3px solid #007bff; }
    .content { margin-left: 250px; padding: 20px; }
    .navbar { background-color: #007bff; }
</style>

@php
    $u = auth()->user();
    $can = fn($perm) => $u && ($u->isSuperAdmin() || $u->hasPermission($perm));
@endphp

<div class="sidebar">
    <h5 class="text-primary text-center fw-bold">
        <u style="color: #ff9800;">SafeTower Naucalpan</u>
    </h5>
    <br>

    {{-- Dashboard --}}
    @if($can('dashboard'))
        <a href="{{ route('admin.dashboard') }}"
           class="{{ request()->is('admin/dashboard') ? 'active' : '' }}">
           Panel de Administración
        </a>
    @endif

    {{-- Reportes --}}
    @if($can('reports'))
        <a href="{{ route('admin.chats') }}"
           class="{{ request()->is('admin/chats') || request()->is('admin/chat/*') ? 'active' : '' }}">
           Reportes
        </a>
    @endif

    {{-- Usuarios --}}
    @if($can('users'))
        <a href="{{ route('admin.users.index') }}"
           class="{{ request()->is('admin/users') || request()->is('admin/users/*') ? 'active' : '' }}">
           Usuarios
        </a>
    @endif

    {{-- Empresas --}}
    @if($can('companies'))
        <a href="{{ route('admin.companies.index') }}"
           class="{{ request()->is('admin/companies') || request()->is('admin/companies/*') ? 'active' : '' }}">
           Empresas
        </a>
    @endif

    {{-- Salas --}}
    @if($can('rooms'))
        <a href="{{ route('admin.rooms.index') }}"
           class="{{ request()->is('admin/rooms') || request()->is('admin/rooms/*') ? 'active' : '' }}">
           Salas
        </a>
    @endif

    {{-- Reservas --}}
    @if($can('reservations'))
        <a href="{{ route('admin.reservations.index') }}"
           class="{{ request()->is('admin/reservations') || request()->is('admin/reservations/*') ? 'active' : '' }}">
           Reservas
        </a>
    @endif

    {{-- Categorías del Marketplace --}}
    @if($can('marketplace_categories'))
        <a href="{{ route('admin.marketplace_categories.index') }}"
           class="{{ request()->is('admin/marketplace_categories') || request()->is('admin/marketplace_categories/*') ? 'active' : '' }}">
           Categorías del Marketplace
        </a>
    @endif

    {{-- Anuncios del Marketplace --}}
    @if($can('marketplace'))
        <a href="{{ route('admin.marketplace.index') }}"
           class="{{ request()->is('admin/marketplace') || request()->is('admin/marketplace/*') ? 'active' : '' }}">
           Anuncios del Marketplace
        </a>
    @endif

    {{-- Mi Perfil (always for logged-in user; no permission required) --}}
    <a href="{{ route('admin.profile') }}"
       class="{{ request()->is('admin/profile') ? 'active' : '' }}">
       Mi Perfil
    </a>

    {{-- Logout --}}
    <a href="{{ route('admin.logout') }}">Cerrar Sesión</a>
</div>
