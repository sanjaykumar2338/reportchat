@extends('layouts.admin')

@section('content')
@include('layouts.sidebar')

<div class="container mt-4" style="width: 92%; margin-left: 176px;">
    <h2>Detalles del Usuario</h2>
    <div class="card p-4">
        <h4>Nombre: {{ $user->name }}</h4>
        <p><strong>Correo:</strong> {{ $user->email }}</p>
        <p><strong>Teléfono:</strong> {{ $user->phone }}</p>
        @if($user->companyRelation?->name)
            <p><strong>Empresa:</strong> {{ $user->companyRelation->name ?? '-' }}</p>
        @endif
        <p><strong>Creado el:</strong> {{ $user->created_at->format('d M Y, h:i A') }}</p>

        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary mt-3">Volver</a>
    </div>
</div>
@endsection
