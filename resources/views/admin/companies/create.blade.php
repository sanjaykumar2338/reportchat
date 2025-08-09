@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4" style="margin-left: 176px;">
    <h2>Agregar Nueva Empresa</h2>

    <form action="{{ route('admin.companies.store') }}" method="POST" style="width: 92%;">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label">Nombre de la Empresa</label>
            <input type="text" name="name" class="form-control" placeholder="Ingrese el nombre de la empresa" required>
        </div>

        <button type="submit" class="btn btn-success">Guardar Empresa</button>
        <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary">Volver</a>
    </form>
</div>

@endsection
