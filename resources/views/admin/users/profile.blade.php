@extends('layouts.admin')

@section('content')
@include('layouts.sidebar')

<style>
    /* Shake + highlight together */
    .attention {
        animation: shakeAnim 0.5s ease, fadeHighlight 1.5s ease-out;
    }

    @keyframes shakeAnim {
        0%   { transform: translateX(0); }
        20%  { transform: translateX(-5px); }
        40%  { transform: translateX(5px); }
        60%  { transform: translateX(-5px); }
        80%  { transform: translateX(5px); }
        100% { transform: translateX(0); }
    }

    @keyframes fadeHighlight {
        0%   { background-color: #fff3cd; }  /* light yellow */
        50%  { background-color: #e6ffcc; }  /* soft green */
        100% { background-color: transparent; }
    }
</style>

<div class="container mt-4" style="margin-left: 176px; width: 92%;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Mi Perfil</h2>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Volver</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Por favor corrige los siguientes errores:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.profile.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="name" class="form-control"
                   value="{{ old('name', $user->name) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Usuario</label>
            <input type="text" name="username" class="form-control"
                   value="{{ old('username', $user->username) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Correo</label>
            <input type="email" name="email" class="form-control"
                   value="{{ old('email', $user->email) }}" required>
        </div>

        {{-- Teléfono field --}}
        <div id="phoneField" class="mb-3">
            <label class="form-label">Teléfono</label>
            <input type="text" name="phone" class="form-control"
                value="{{ old('phone', $user->phone) }}">

            {{-- ✅ Only show helper for admin --}}
            @if($user->role === 'admin')
                <small id="phoneHelp" class="form-text text-muted">
                    Si deseas recibir notificaciones por WhatsApp, ingresa tu número con código de país, sin el signo +
                    (ejemplo: 5215512345678).
                </small>
            @endif
        </div>

        <div class="mb-3">
            <label class="form-label">Contraseña (dejar vacío para no cambiar)</label>
            <input type="password" name="password" class="form-control">
        </div>

        {{-- Empresa (opcional) --}}
        @isset($companies)
        <div class="mb-3">
            <label class="form-label">Empresa</label>
            <select name="company" class="form-control">
                <option value="">Seleccionar Empresa</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}"
                        {{ (old('company', $user->company_id) == $company->id) ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>
        @endisset

        {{-- Checkbox only for admin --}}
        @if($user->role === 'admin')
            <div class="form-check mb-3">
                <input type="checkbox" name="whatsapp_notifications" value="1" class="form-check-input"
                    id="whatsapp_notifications"
                    {{ old('whatsapp_notifications', $user->whatsapp_notifications ?? false) ? 'checked' : '' }}>
                <label class="form-check-label" for="whatsapp_notifications">
                    Recibir notificaciones por WhatsApp
                </label>
            </div>
        @endif

        <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const cb   = document.getElementById('whatsapp_notifications');
  const phoneField = document.getElementById('phoneField');

  if (cb && phoneField) {
    cb.addEventListener('change', function () {
      if (cb.checked) {
        // restart animation
        phoneField.classList.remove('attention');
        void phoneField.offsetWidth;
        phoneField.classList.add('attention');
      }
    });
  }
});
</script>
@endsection