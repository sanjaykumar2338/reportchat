@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4" style="margin-left: 176px;">
    <h2>View Company</h2>

    <div class="card" style="width: 92%;">
        <div class="card-body">
            <h5 class="card-title">{{ $company->name }}</h5>
            <p class="card-text">
                <strong>Created At:</strong> {{ $company->created_at->format('d M Y, h:i A') }}<br>
                <strong>Updated At:</strong> {{ $company->updated_at->format('d M Y, h:i A') }}
            </p>

            <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary">Back to Companies</a>
            <a href="{{ route('admin.companies.edit', $company->id) }}" class="btn btn-primary">Edit Company</a>
        </div>
    </div>
</div>

@endsection
