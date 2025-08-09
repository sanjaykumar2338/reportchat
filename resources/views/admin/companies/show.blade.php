@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4" style="margin-left: 176px;">
    <h2>Ver Empresa</h2>

    <div class="card" style="width: 92%;">
        <div class="card-body">
            <h5 class="card-title">{{ $company->name }}</h5>
            <p class="card-text">
                <strong>Creada el:</strong> {{ $company->created_at->format('d M Y, h:i A') }}<br>
                <strong>Actualizada el:</strong> {{ $company->updated_at->format('d M Y, h:i A') }}
            </p>

            <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary">Volver a Empresas</a>
            <a href="{{ route('admin.companies.edit', $company->id) }}" class="btn btn-primary">Editar Empresa</a>
        </div>
    </div>
</div>

@endsection
