@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4" style="margin-left: 176px;">
    <h2>Edit Company</h2>

    <form action="{{ route('admin.companies.update', $company->id) }}" method="POST" style="width: 92%;">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="name" class="form-label">Company Name</label>
            <input type="text" name="name" class="form-control" value="{{ $company->name }}" required>
        </div>

        <button type="submit" class="btn btn-primary">Update Company</button>
        <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>

@endsection
