@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-10">
<h2 style="
    margin-left: 176px;">All Chats</h2>
    
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
                <td><span class="badge bg-{{ $chat->status == 'open' ? 'success' : 'danger' }}">{{ ucfirst($chat->status) }}</span></td>
                <td>{{ $chat->created_at->format('d M Y, h:i A') }}</td>
                <td>
                    <a href="{{ route('admin.view.chat', $chat->id) }}" class="btn btn-sm btn-primary">View</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $chats->links('vendor.pagination.bootstrap-5') }}


</div>
@endsection
