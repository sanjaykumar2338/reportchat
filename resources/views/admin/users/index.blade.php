@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4">
    <h2 style="
    margin-left: 176px;">Users Management</h2>
    <table class="table table-bordered" style="width: 92%;
    margin-left: 176px;">   
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td>{{ $user->id }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->phone }}</td>
                <td>
                    <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-sm btn-primary">View</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $users->links('vendor.pagination.bootstrap-5') }}</div>

    <script>
       document.addEventListener("DOMContentLoaded", function () {
            let elements = document.querySelectorAll('.text-muted'); // Select all elements with class 'text-muted'
            
            elements.forEach(element => {
                if (element.parentElement) { 
                    element.parentElement.style.cssText += "margin-left: 175px !important;"; // Apply margin-left to the parent
                }
            });
        });
    </script>
@endsection
