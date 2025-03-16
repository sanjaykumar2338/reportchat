@extends('layouts.admin')

@section('content')
@include('layouts.sidebar')

<div class="container mt-4" style="width: 92%; margin-left: 176px;">
    <h2>Report: {{ $chat->title }}</h2>
    <p><strong>Location:</strong> {{ $chat->location }}</p>

    <p><strong>Status:</strong> 
        <span id="status-badge" class="badge bg-{{ $chat->status == 'open' ? 'success' : ($chat->status == 'pending' ? 'warning' : 'danger') }}">
            {{ ucfirst($chat->status) }}
        </span>
    </p>

    <!-- Status Dropdown -->
    <div class="mb-3">
        <label for="status" class="form-label"><strong>Change Status:</strong></label>
        <select id="status" class="form-control" data-chat-id="{{ $chat->id }}">
            <option value="open" {{ $chat->status == 'open' ? 'selected' : '' }}>Open</option>
            <option value="pending" {{ $chat->status == 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="refused" {{ $chat->status == 'refused' ? 'selected' : '' }}>Refused</option>
            <option value="closed" {{ $chat->status == 'closed' ? 'selected' : '' }}>Closed</option>
        </select>
    </div>

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

<!-- AJAX to Update Status -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("status").addEventListener("change", function () {
            let chatId = this.getAttribute("data-chat-id");
            let newStatus = this.value;

            fetch(`/admin/chats/${chatId}/update-status`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let statusBadge = document.getElementById("status-badge");
                    statusBadge.innerText = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                    
                    // Change badge color dynamically
                    statusBadge.className = "badge bg-" + 
                        (newStatus === "open" ? "success" :
                        (newStatus === "pending" ? "warning" :
                        (newStatus === "refused" ? "danger" : "secondary")));
                }
            })
            .catch(error => console.error("Error updating status:", error));
        });
    });
</script>

@endsection
