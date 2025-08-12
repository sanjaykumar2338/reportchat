<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeTower Naucalpan - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- En tu diseño o índice de reservas -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
    <link rel="icon" type="image/x-icon" href="{{asset('images/fav.webp')}}">
</head>
<body>

<!-- Barra de navegación -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="{{ route('admin.dashboard') }}">Administración</a>
        <div class="ml-auto">
            <a class="btn btn-danger btn-sm" href="{{ route('admin.logout') }}">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<!-- Contenido principal -->

@if(session('success'))
    <div class="alert alert-success" style="text-align:center;">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger" style="text-align:center;">
        {{ session('error') }}
    </div>
@endif

<div class="container mt-4">
    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
@vite(['resources/js/app.js'])

</body>
</html>
