@extends('layouts.admin')

@section('content')
@include('layouts.sidebar')

<div class="container mt-4" style="width: 92%;
    margin-left: 176px;">
    <h2>User Details</h2>
    <div class="card p-4">
        <h4>Name: {{ $user->name }}</h4>
        <p>Email: {{ $user->email }}</p>
        <p>Phone: {{ $user->phone }}</p>
        <p>Created At: {{ $user->created_at->format('d M Y, h:i A') }}</p>
        <a href="{{ route('admin.users') }}" class="btn btn-secondary">Back</a>
    </div>
</div>
@endsection
