@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-10">
<h2 style="
    margin-left: 176px;">All Reports</h2>
    
    <table class="table table-bordered" style="width: 92%;
    margin-left: 176px;">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Location</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($chats as $chat)
            <tr>
                <td>{{ $chat->id }}</td>
                <td>{{ $chat->title }}</td>
                <td>{{ $chat->location }}</td>
                <td>
                    <span class="badge bg-{{ 
                        $chat->status == 'open' ? 'success' : 
                        ($chat->status == 'pending' ? 'warning' : 
                        ($chat->status == 'refused' ? 'danger' : 'secondary')) 
                    }}" id="status-badge-{{ $chat->id }}">
                        {{ ucfirst($chat->status) }}
                    </span>
                </td>
                <td>{{ $chat->created_at->format('d M Y, h:i A') }}</td>
                <td>
                    <a href="{{ route('admin.view.chat', $chat->id) }}" class="btn btn-sm btn-primary">View</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $chats->links('vendor.pagination.bootstrap-5') }}

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
</div>
@endsection
