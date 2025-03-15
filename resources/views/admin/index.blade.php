@extends('layouts.admin')

@section('content')
    <h2>All Chats</h2>
    <table class="table table-bordered" style="width: 92%;
    margin-left: 176px;">
        <thead class="table-dark">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Location</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($chats as $chat)
                <tr>
                    <td>{{ $chat->id }}</td>
                    <td>{{ $chat->title }}</td>
                    <td>{{ $chat->location }}</td>
                    <td>{{ $chat->status }}</td>
                    <td>{{ $chat->created_at }}</td>
                    <td>
                        <a href="{{ route('admin.view.chat', $chat->id) }}">View</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
