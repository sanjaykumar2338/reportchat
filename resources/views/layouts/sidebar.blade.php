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
        padding: 12px 15px;
        text-decoration: none;
        font-size: 16px;
        color: white;
        display: block;
        transition: 0.3s;
        border-left: 3px solid transparent; /* Default border */
    }
    .sidebar a:hover, 
    .sidebar a.active {
        background-color: #495057;
        border-left: 3px solid #007bff; /* Highlight active link */
    }
    .content {
        margin-left: 250px;
        padding: 20px;
    }
    .navbar {
        background-color: #007bff;
    }
</style>

<div class="sidebar">
<h5 class="text-primary text-center fw-bold">
    <u style="color: #ff9800;">SafeTower Naucalpan</u>
</h5>
    <br>
    <a href="{{ route('admin.dashboard') }}" 
       class="{{ request()->is('admin/dashboard') ? 'active' : '' }}">
       Dashboard
    </a>

    <a href="{{ route('admin.chats') }}" 
       class="{{ request()->is('admin/chats') || request()->is('admin/chat/*') ? 'active' : '' }}">
       Reports
    </a>

    <a href="{{ route('admin.users.index') }}" 
       class="{{ request()->is('admin/users') || request()->is('admin/users/*') ? 'active' : '' }}">
       Users
    </a>

    <a href="{{ route('admin.companies.index') }}" 
       class="{{ request()->is('admin/companies') || request()->is('admin/companies/*') ? 'active' : '' }}">
       Companies
    </a>

    <a href="{{ route('admin.rooms.index') }}" 
       class="{{ request()->is('admin/rooms') || request()->is('admin/rooms/*') ? 'active' : '' }}">
       Rooms
    </a>

     <a href="{{ route('admin.reservations.index') }}" 
       class="{{ request()->is('admin/reservations') || request()->is('admin/reservations/*') ? 'active' : '' }}">
       Reservations
    </a>
    
    <a class="" href="{{ route('admin.logout') }}">Logout</a>
</div>

