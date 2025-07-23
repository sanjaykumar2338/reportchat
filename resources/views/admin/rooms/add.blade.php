@extends('layouts.admin')

@section('content')

@include('layouts.sidebar')

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3" style="margin-left: 176px; width: 92%;">
        <h2>{{ isset($room) ? 'Edit Room' : 'Add Room' }}</h2>
        <a href="{{ route('admin.rooms.index') }}" class="btn btn-secondary">Back</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" style="margin-left: 176px; width: 92%;">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ isset($room) ? route('admin.rooms.update', $room->id) : route('admin.rooms.store') }}" method="POST" enctype="multipart/form-data" style="margin-left: 176px; width: 92%;">
        @csrf
        @if(isset($room))
            @method('PUT')
        @endif

        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $room->name ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Floor</label>
            <input type="text" name="floor" class="form-control" value="{{ old('floor', $room->floor ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category" class="form-control" required>
                <option value="">Select Category</option>
                <option value="Sala" {{ old('category', $room->category ?? '') == 'Sala' ? 'selected' : '' }}>Sala</option>
                <option value="Auditorio" {{ old('category', $room->category ?? '') == 'Auditorio' ? 'selected' : '' }}>Auditorio</option>
                <option value="Roof" {{ old('category', $room->category ?? '') == 'Roof' ? 'selected' : '' }}>Roof</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="company">Company</label>
            <select name="company" id="company" class="form-control">
                <option value="">Select a company</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}"
                        {{ old('company', isset($room) ? $room->company : '') == $company->id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3 row">
            <div class="col">
                <label class="form-label">Available From</label>
                <input type="time" name="available_from" class="form-control" value="{{ old('available_from', $room->available_from ?? '') }}" required>
            </div>
            <div class="col">
                <label class="form-label">Available To</label>
                <input type="time" name="available_to" class="form-control" value="{{ old('available_to', $room->available_to ?? '') }}" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Capacity</label>
            <input type="number" name="capacity" class="form-control" value="{{ old('capacity', $room->capacity ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Image</label>
            <input type="file" name="image" class="form-control">
            @if(isset($room) && $room->image_url)
                <div class="mt-2">
                    <img src="{{ url($room->image_url) }}" alt="Room Image" width="120">
                    <div class="form-check mt-2">
                        <input type="checkbox" name="remove_image" class="form-check-input" id="remove_image">
                        <label class="form-check-label" for="remove_image">Remove existing image</label>
                    </div>
                </div>
            @endif
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-primary">{{ isset($room) ? 'Update Room' : 'Create Room' }}</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const timeInputs = document.querySelectorAll('input[type="time"]');
        const fromInput = document.querySelector('input[name="available_from"]');
        const toInput = document.querySelector('input[name="available_to"]');
        const form = document.querySelector('form');

        function roundTime(input) {
            if (!input.value) return;
            let [hour, minute] = input.value.split(':');
            minute = parseInt(minute);
            if (minute !== 0 && minute !== 30) {
                let rounded = minute < 30 ? '00' : '30';
                input.value = `${hour.padStart(2, '0')}:${rounded}`;
            }
        }

        function isValidTimeRange() {
            if (fromInput.value && toInput.value) {
                const [fromHour, fromMin] = fromInput.value.split(':').map(Number);
                const [toHour, toMin] = toInput.value.split(':').map(Number);

                const fromMinutes = fromHour * 60 + fromMin;
                const toMinutes = toHour * 60 + toMin;

                return toMinutes > fromMinutes;
            }
            return true;
        }

        function validateTimeOrder() {
            if (!isValidTimeRange()) {
                alert('End time must be after start time.');
                toInput.value = '';
            }
        }

        // Force round immediately on load
        roundTime(fromInput);
        roundTime(toInput);

        timeInputs.forEach(input => {
            input.addEventListener('change', function () {
                roundTime(this);
                validateTimeOrder();
            });

            // prevent manual typing (optional)
            input.addEventListener('keydown', e => e.preventDefault());
        });

        form.addEventListener('submit', function (e) {
            roundTime(fromInput);
            roundTime(toInput);
            if (!isValidTimeRange()) {
                alert('Cannot submit: "Available To" time must be after "Available From" time.');
                e.preventDefault();
            }
        });
    });
</script>

@endsection
