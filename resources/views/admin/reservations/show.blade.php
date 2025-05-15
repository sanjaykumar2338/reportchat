@extends('layouts.admin')

@section('content')
@include('layouts.sidebar')

<div class="container mt-4" style="width: 92%; margin-left: 176px;">
    <h2>User Details</h2>
    <div class="card p-4">
        <h4>Name: {{ $user->name }}</h4>
        <p><strong>Email:</strong> {{ $user->email }}</p>
        <p><strong>Phone:</strong> {{ $user->phone }}</p>
        @if($user->companyRelation?->name)
            <p><strong>Company:</strong> {{ $user->companyRelation->name ?? '-' }}</p>
        @endif
        <p><strong>Created At:</strong> {{ $user->created_at->format('d M Y, h:i A') }}</p>

        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary mt-3">Back</a>
    </div>
</div>
@endsection
