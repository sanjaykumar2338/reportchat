@extends('layouts.admin')

@section('content')
@include('layouts.sidebar')

<div class="container mt-4" style="width: 92%; margin-left: 176px;">
    <h2>Chat: {{ $chat->title }}</h2>
    <p><strong>Location:</strong> {{ $chat->location }}</p>
    <p><strong>Status:</strong> 
        <span class="badge bg-{{ $chat->status == 'open' ? 'success' : 'danger' }}">{{ ucfirst($chat->status) }}</span>
    </p>

    <h4>Messages</h4>
    <div class="border p-3 mb-3" id="chat-box" style="max-height: 300px; overflow-y: auto;">
        @foreach($chat->messages as $message)
            <div class="mb-2">
                <strong>{{ $message->is_admin ? 'Admin' : 'User' }}:</strong> {{ $message->message }}
                @if($message->image)
                    <br><img src="{{ asset($message->image) }}" alt="Image" width="100">
                @endif
            </div>
        @endforeach
    </div>

    <h4>Send a Message</h4>
    <form action="{{ route('admin.send.message', $chat->id) }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Message:</label>
            <textarea name="message" class="form-control" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Send</button>
    </form>
</div>


@endsection
