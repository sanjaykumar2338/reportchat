@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4" style="width: 92%; margin-left: 176px;">
    <h2>All Reports</h2>

    <!-- Search and Filter Form -->
    <form action="{{ route('admin.chats') }}" method="GET" class="mb-3">
        <div class="row">
            <!-- Search Input -->
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search by title..." value="{{ request('search') }}">
            </div>

            <!-- Status Dropdown -->
            <div class="col-md-3">
                <select name="status" class="form-control">
                    <option value="">Filter by Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="solved" {{ request('status') == 'solved' ? 'selected' : '' }}>Solved</option>
                    <option value="refused" {{ request('status') == 'refused' ? 'selected' : '' }}>Refused</option>
                </select>
            </div>

            <!-- Submit Button -->
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>

            <!-- Reset Button -->
            <div class="col-md-2">
                <a href="{{ route('admin.chats') }}" class="btn btn-secondary w-100">Reset</a>
            </div>
        </div>
    </form>

    <!-- Reports Table -->
    <table class="table table-bordered">
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
            @if($chats->count() > 0)
                @foreach($chats as $chat)
                    <tr>
                        <td>{{ $chat->id }}</td>
                        <td>{{ $chat->title }}</td>
                        <td>{{ $chat->location }}</td>
                        <td>
                            <span class="badge bg-{{ 
                                $chat->status == 'solved' ? 'success' : 
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
            @else
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        <strong>No results found.</strong>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Pagination -->
    {{ $chats->links('vendor.pagination.bootstrap-5') }}

</div>

@endsection
