@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

@php $editing = isset($listing); @endphp

<div class="container mt-4" style="margin-left:176px; width:92%;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>{{ $editing ? 'Editar Anuncio' : 'Crear Anuncio' }}</h2>
    <a href="{{ route('admin.marketplace.index') }}" class="btn btn-secondary">Volver</a>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  <form method="POST" enctype="multipart/form-data"
        action="{{ $editing ? route('admin.marketplace.update',$listing->id) : route('admin.marketplace.store') }}">
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Usuario</label>
        <select name="user_id" class="form-control" required>
          <option value="">-- Seleccione --</option>
          @foreach($users as $u)
            <option value="{{ $u->id }}" @selected(old('user_id', $listing->user_id ?? '')==$u->id)>{{ $u->name }} ({{ $u->username }})</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-6 mb-3">
        <label class="form-label">Categoría</label>
        <select name="category_id" class="form-control" required>
          <option value="">-- Seleccione --</option>
          @foreach($categories as $c)
            <option value="{{ $c->id }}" @selected(old('category_id', $listing->category_id ?? '')==$c->id)>{{ $c->name }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Título</label>
      <input type="text" name="title" class="form-control" value="{{ old('title', $listing->title ?? '') }}" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Descripción</label>
      <textarea name="description" class="form-control" rows="4">{{ old('description', $listing->description ?? '') }}</textarea>
    </div>

    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Precio</label>
        <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $listing->price ?? '') }}">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">WhatsApp</label>
        <input type="text" name="whatsapp" class="form-control" value="{{ old('whatsapp', $listing->whatsapp ?? '') }}" required>
      </div>
      <div class="col-md-4 mb-3">
        <div class="form-check mt-4">
          <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
            {{ old('is_active', $listing->is_active ?? true) ? 'checked' : '' }}>
          <label class="form-check-label" for="is_active">Activo</label>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Publicado en</label>
        <input type="datetime-local" name="published_at" class="form-control"
               value="{{ old('published_at', optional($listing->published_at ?? null)->format('Y-m-d\TH:i')) }}">
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Finaliza en</label>
        <input type="datetime-local" name="ends_at" class="form-control"
               value="{{ old('ends_at', optional($listing->ends_at ?? null)->format('Y-m-d\TH:i')) }}">
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Imágenes (múltiples)</label>
      <input type="file" name="images[]" class="form-control" multiple>
      <small class="text-muted">Se permiten múltiples imágenes (máx. 4MB cada una).</small>
    </div>

    @if(!empty($listing?->images))
      <div class="mb-3">
        <label class="form-label">Imágenes actuales</label>
        <div class="d-flex flex-wrap gap-3">
          @foreach($listing->images as $img)
            <div class="border p-2 text-center">
              <img src="{{ asset('storage/'.$img) }}" alt="" style="height:80px; display:block; margin-bottom:8px;">
              <div class="form-check">
                <input class="form-check-input" name="remove_images[]" type="checkbox" value="{{ $img }}" id="rm_{{ md5($img) }}">
                <label class="form-check-label" for="rm_{{ md5($img) }}">Quitar</label>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endif

    <button class="btn btn-primary">{{ $editing ? 'Actualizar' : 'Crear' }}</button>
  </form>
</div>
@endsection
