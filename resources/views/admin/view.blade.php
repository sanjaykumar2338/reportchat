@extends('layouts.admin')

@section('content')
    <h2>Chat #{{ $chat->id }}</h2>
    <p><strong>Title:</strong> {{ $chat->title }}</p>
    <p><strong>Description:</strong> {{ $chat->description }}</p>
    <p><strong>Location:</strong> {{ $chat->location }}</p>

    <h3>Messages</h3>
    <div id="chat-box" style="border: 1px solid #ccc; padding: 10px; max-height: 300px; overflow-y: auto;">
        @foreach ($chat->messages as $message)
            <p>
                @if ($message->is_admin)
                    <strong>Admin:</strong>
                @else
                    <strong>User:</strong>
                @endif
                {{ $message->message }}
                @if ($message->image)
                    <br><img src="{{ asset($message->image) }}" alt="Image" width="100">
                @endif
            </p>
        @endforeach
    </div>

    <h3>Send Message</h3>
    <form action="{{ route('admin.send.message', $chat->id) }}" method="POST">
        @csrf
        <textarea name="message" required class="form-control mb-2"></textarea>
        <button type="submit" class="btn btn-primary">Send</button>
    </form>

    <!-- Reverb JS -->
    <script src="{{ asset('js/reverb.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const reverb = new Reverb({
                host: "http://127.0.0.1:8000",
            });

            let chatId = "{{ $chat->id }}";

            // Listen for new messages
            reverb.channel("chat." + chatId).listen("App\\Events\\MessageSent", function (data) {
                console.log("New Message:", data);

                let chatBox = document.getElementById("chat-box");
                let newMessage = `
                    <p>
                        <strong>${data.admin_id ? 'Admin' : 'User'}:</strong> 
                        ${data.message}
                        ${data.image ? `<br><img src="${data.image}" width="100">` : ''}
                    </p>
                `;

                chatBox.innerHTML += newMessage;
                chatBox.scrollTop = chatBox.scrollHeight;
            });
        });
    </script>
@endsection
