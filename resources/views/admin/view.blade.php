@extends('layouts.admin')

@section('content')
    <h2>Chat #{{ $chat->id }}</h2>
    <p><strong>Title:</strong> {{ $chat->title }}</p>
    <p><strong>Description:</strong> {{ $chat->description }}</p>
    <p><strong>Location:</strong> {{ $chat->location }}</p>

    <h3>Messages</h3>
    <div>
        @foreach ($chat->messages as $message)
            <p>
                @if ($message->is_admin)
                    <strong>Admin:</strong>
                @else
                    <strong>User:</strong>
                @endif
                {{ $message->message }}
            </p>
        @endforeach
    </div>

    <h3>Send Message</h3>
    <form action="{{ route('admin.send.message', $chat->id) }}" method="POST">
        @csrf
        <textarea name="message" required></textarea>
        <button type="submit">Send</button>
    </form>
@endsection
