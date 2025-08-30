@extends('layouts.admin')

@section('content')
@include('layouts.sidebar')

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3" style="margin-left:176px;width:92%;">
    <h2>Subir CSV de Usuarios</h2>
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Volver</a>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger" style="margin-left:176px;width:92%;">
      <strong>Por favor corrige los siguientes errores:</strong>
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if (session('csv_errors'))
    <div class="alert alert-warning" style="margin-left:176px;width:92%;">
      <strong>Advertencias:</strong>
      <ul class="mb-0">
        @foreach (session('csv_errors') as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('admin.users.importCsv') }}" enctype="multipart/form-data" style="margin-left:176px;width:92%;">
    @csrf
    <div class="mb-3">
      <label class="form-label">Archivo CSV</label>
      <input type="file" name="csv" class="form-control" accept=".csv" required>
      <small class="text-muted">
        Cabeceras requeridas: <code>Nombre, Usuario, Correo, Telefono, Contraseña, Empresa</code>.
        Todos los usuarios creados serán de tipo <strong>Usuario</strong> (no Admin/Superadmin).
      </small>
    </div>

    <div class="mb-3 d-flex gap-2">
      <button type="submit" class="btn btn-primary">Subir CSV</button>
      <button type="button" class="btn btn-outline-secondary" onclick="downloadPlantilla()">Descargar plantilla</button>
    </div>
  </form>
</div>

<script>
function downloadPlantilla(){
  const csv =
`Nombre,Usuario,Correo,Telefono,Contraseña,Empresa
Juan Pérez,juanp,jperez@example.com,5551234567,Secret123,Empresa Uno
María López,marial,marial@example.com,5559876543,Clave456,Empresa Dos
`;
  const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url;
  a.download = 'usuarios_plantilla.csv';
  document.body.appendChild(a);
  a.click();
  URL.revokeObjectURL(url);
  a.remove();
}
</script>
@endsection
