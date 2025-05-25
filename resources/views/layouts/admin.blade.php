<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeTower Naucalpan - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- In your layout or reservation index -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
    <link rel="icon" type="image/x-icon" href="{{asset('images/fav.webp')}}">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="{{ route('admin.dashboard') }}">Admin Panel</a>
        <div class="ml-auto">
            <a class="btn btn-danger btn-sm" href="{{ route('admin.logout') }}">Logout</a>
        </div>
    </div>
</nav>

<!-- Main Content -->

@if(session('success'))
    <div class="alert alert-success" style="text-align:center;">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-error" style="text-align:center;">
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
