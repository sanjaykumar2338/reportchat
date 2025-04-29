@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3" style="margin-left: 176px; width: 92%;">
        <h2>{{ isset($user) ? 'Edit User' : 'Create User' }}</h2>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <form action="{{ isset($user) ? route('admin.users.update', $user->id) : route('admin.users.store') }}" method="POST" style="margin-left: 176px; width: 92%;">
        @csrf
        @if(isset($user))
            @method('PUT')
        @endif

        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone ?? '') }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" {{ isset($user) ? '' : 'required' }}>
        </div>

        <div class="mb-3">
            <label class="form-label">Company</label>
            <select name="company" class="form-control">
                <option value="">Select Company</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" {{ (old('company', $user->company ?? '') == $company->id) ? 'selected' : '' }}>{{ $company->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-primary">{{ isset($user) ? 'Update' : 'Create' }}</button>
        </div>
    </form>
</div>

@endsection
