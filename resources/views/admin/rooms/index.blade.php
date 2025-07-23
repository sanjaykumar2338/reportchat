@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3" style="margin-left: 176px; width: 92%;">
        <h2>Room Management</h2>
        <a href="{{ route('admin.rooms.create') }}" class="btn btn-success">+ Add Room</a>
    </div>

    <!-- Search Form -->
    <form method="GET" action="{{ route('admin.rooms.index') }}" style="width: 92%; margin-left: 176px;" class="mb-3">
        <div class="row">
            <div class="col-md-3">
                <input type="text" name="name" class="form-control" placeholder="Search by Room Name" value="{{ request('name') }}">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-control">
                    <option value="">All Categories</option>
                    <option value="Sala" {{ request('category') == 'Sala' ? 'selected' : '' }}>Sala</option>
                    <option value="Auditorio" {{ request('category') == 'Auditorio' ? 'selected' : '' }}>Auditorio</option>
                    <option value="Roof" {{ request('category') == 'Roof' ? 'selected' : '' }}>Roof</option>
                </select>
            </div>
            <div class="col-md-6 d-flex">
                <button type="submit" class="btn btn-primary me-2">Search</button>
                <a href="{{ route('admin.rooms.index') }}" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>

    <table class="table table-bordered" style="width: 92%; margin-left: 176px;">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Floor</th>
                <th>Category</th>
                <th>Capacity</th>
                <th>Availability</th>
                <th>Image</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rooms as $room)
                <tr>
                    <td>{{ $room->id }}</td>
                    <td>{{ $room->name }}</td>
                    <td>{{ $room->floor }}</td>
                    <td>{{ $room->category }}</td>
                    <td>{{ $room->capacity }}</td>
                    <td>
                        {{ \Carbon\Carbon::parse($room->available_from)->format('g:i A') }}
                        -
                        {{ \Carbon\Carbon::parse($room->available_to)->format('g:i A') }}
                    </td>
                    <td>
                        @if($room->image_url)
                            <a href="{{ url($room->image_url) }}" target="_blank">
                                <img src="{{ url($room->image_url) }}" alt="Room Image" width="60">
                            </a>
                        @else
                            -
                        @endif
                    </td>           
                    <td>
                        <a href="{{ route('admin.rooms.edit', $room->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('admin.rooms.destroy', $room->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button onclick="return confirm('Are you sure?')" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted"><strong>No rooms found.</strong></td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="mt-3" style="margin-left: 176px;">
        {{ $rooms->links('vendor.pagination.bootstrap-5') }}
    </div>
</div>

@endsection
