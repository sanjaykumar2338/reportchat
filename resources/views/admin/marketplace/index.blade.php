@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3" style="margin-left:176px; width:92%;">
    <h2>Anuncios del Mercado</h2>
    <a href="{{ route('admin.marketplace.create') }}" class="btn btn-success">+ Crear Anuncio</a>
  </div>

  <form method="GET" action="{{ route('admin.marketplace.index') }}" style="width:92%; margin-left:176px;" class="mb-3">
    <div class="row">
      <div class="col-md-3">
        <input type="text" name="q" class="form-control" placeholder="Buscar título/desc/WhatsApp" value="{{ request('q') }}">
      </div>
      <div class="col-md-3">
        <select name="category_id" class="form-control">
          <option value="">-- Categoría --</option>
          @foreach($categories as $c)
            <option value="{{ $c->id }}" @selected(request('category_id')==$c->id)>{{ $c->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <select name="user_id" class="form-control">
          <option value="">-- Usuario --</option>
          @foreach($users as $u)
            <option value="{{ $u->id }}" @selected(request('user_id')==$u->id)>{{ $u->name }} ({{ $u->username }})</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <select name="active" class="form-control">
          <option value="">-- Activo? --</option>
          <option value="1" @selected(request('active')==='1')>Sí</option>
          <option value="0" @selected(request('active')==='0')>No</option>
        </select>
      </div>
      <div class="col-md-1 d-grid">
        <button class="btn btn-primary">Buscar</button>
      </div>
    </div>
  </form>

  <table class="table table-bordered" style="width:92%; margin-left:176px;">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Título</th>
        <th>Categoría</th>
        <th>Precio</th>
        <th>Usuario</th>
        <th>Activo</th>
        <th>Publicado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      @forelse($listings as $l)
        <tr>
          <td>{{ $l->id }}</td>
          <td>{{ $l->title }}</td>
          <td>{{ $l->category->name ?? '-' }}</td>
          <td>${{ number_format($l->price ?? 0, 2) }}</td>
          <td>{{ $l->user->name ?? '-' }}</td>
          <td><span class="badge bg-{{ $l->is_active ? 'success' : 'secondary' }}">{{ $l->is_active ? 'Sí' : 'No' }}</span></td>
          <td>{{ optional($l->published_at)->format('Y-m-d H:i') }}</td>
          <td>
            <a href="{{ route('admin.marketplace.edit', $l->id) }}" class="btn btn-sm btn-warning">Editar</a>
            <form method="POST" action="{{ route('admin.marketplace.destroy', $l->id) }}" style="display:inline;" onsubmit="return confirm('¿Eliminar anuncio?')">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-danger">Eliminar</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="8" class="text-center text-muted"><strong>No se encontraron anuncios.</strong></td></tr>
      @endforelse
    </tbody>
  </table>

  <div class="mt-3" style="margin-left:176px;">
    {{ $listings->links('vendor.pagination.bootstrap-5') }}
  </div>
</div>
@endsection
