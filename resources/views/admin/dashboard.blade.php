<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeTower Naucalpan - Admin Panel</title>
    <link rel="icon" type="image/x-icon" href="{{asset('images/fav.webp')}}">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            padding: 10px;
            text-decoration: none;
            font-size: 16px;
            color: white;
            display: block;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .navbar {
            background-color: #007bff;
        }
    </style>
</head>
<body>

@include('layouts.sidebar')


<!-- Main Content -->
<div class="content">
        


    <!-- Dashboard Content -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5>Total Chats</h5>
                        <h3>{{ $totalChats }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5>Total Users</h5>
                        <h3>{{ $totalUsers }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5>Active Sessions</h5>
                        <h3>{{ $activeSessions }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
