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

  <form method="POST"
        action="{{ $editing ? route('admin.marketplace.update',$listing->id) : route('admin.marketplace.store') }}"
        enctype="multipart/form-data">
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

    {{-- --------- FUENTE DE IMÁGENES: SUBIR o PEGAR BASE64 --------- --}}
    <div class="mb-2">
      <label class="form-label">Fuente de imágenes</label>
      <div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="img_source" id="src_upload" value="upload" checked>
          <label class="form-check-label" for="src_upload">Subir archivos</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="img_source" id="src_base64" value="base64">
          <label class="form-check-label" for="src_base64">Pegar Base64</label>
        </div>
      </div>
    </div>

    <div id="block_upload" class="mb-3">
      <label class="form-label">Imágenes (múltiples)</label>
      <input type="file" name="images[]" class="form-control" multiple accept="image/*">
      <small class="text-muted">Se permiten múltiples imágenes (máx. 4MB cada una). Se guardarán como archivos en <code>/public/uploads</code> y en la BD solo el nombre (p. ej. <code>image_123_0.jpg</code>).</small>
    </div>

    <div id="block_base64" class="mb-3" style="display:none;">
      <label class="form-label">Imágenes en Base64 (una por línea o JSON array)</label>
      <textarea name="images_base64" class="form-control" rows="5"
        placeholder="data:image/jpeg;base64,.... (línea 1)
data:image/png;base64,.... (línea 2)"></textarea>
      <small class="text-muted">
        Puedes pegar con o sin prefijo <code>data:image/...;base64,</code>. Se decodifican y se guardan en <code>/public/uploads</code>.
      </small>
    </div>

    {{-- Imágenes actuales (cuando edites) --}}
    @if(!empty($listing?->images))
      <div class="mb-3">
        <label class="form-label">Imágenes actuales</label>
        <div class="d-flex flex-wrap gap-3">
          @foreach($listing->images as $img)
            <div class="border p-2 text-center">
              {{-- Mostramos desde /uploads/{filename}, porque la API móvil las guarda en public/uploads --}}
              <img src="{{ asset('uploads/'.$img) }}" alt="" style="height:80px; display:block; margin-bottom:8px;">
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

{{-- Toggle JS (ligero) --}}
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const upload = document.getElementById('src_upload');
    const base64 = document.getElementById('src_base64');
    const blockUpload = document.getElementById('block_upload');
    const blockBase64 = document.getElementById('block_base64');

    function refresh() {
      if (base64.checked) {
        blockBase64.style.display = '';
        blockUpload.style.display = 'none';
      } else {
        blockBase64.style.display = 'none';
        blockUpload.style.display = '';
      }
    }
    upload.addEventListener('change', refresh);
    base64.addEventListener('change', refresh);
  });
</script>
@endsection
