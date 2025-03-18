@extends('layouts.admin')

@section('content')
@include('layouts.sidebar')

<div class="container mt-4" style="width: 92%; margin-left: 176px;">
    <h2>Report: {{ $chat->title }}</h2>
    <p><strong>Location:</strong> {{ $chat->location }}</p>

    <p><strong>Status:</strong> 
    <span id="status-badge" class="badge bg-{{ 
    ($chat->status == 'pending' ? 'warning' : 
    ($chat->status == 'solved' ? 'success' : 
    ($chat->status == 'refused' ? 'danger' : 'secondary'))) }}">
    {{ ucfirst($chat->status) }}
    </span>

    </p>

    <!-- Status Dropdown -->
    <div class="mb-3">
        <label for="status" class="form-label"><strong>Change Status:</strong></label>
        <select id="status" class="form-control" data-chat-id="{{ $chat->id }}">
            <option value="pending" {{ $chat->status == 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="refused" {{ $chat->status == 'refused' ? 'selected' : '' }}>Refused</option>
            <option value="solved" {{ $chat->status == 'solved' ? 'selected' : '' }}>Solved</option>
        </select>
    </div>

    <h4>Messages</h4>
    <div class="border p-3 mb-3 chat-container" id="chat-box">
        @foreach($chat->messages as $message)
            <div class="chat-message {{ $message->is_admin ? 'admin-message' : 'user-message' }}">
                <div class="{{ $message->is_admin ? 'admin-message-box' : 'user-message-box' }}">
                    <p class="username">{{ $message->is_admin ? 'Admin' : $message->user->name }}</p>
                    <p class="message-text">{{ $message->message }}</p>
                    @if($message->image)
                        <br><img src="{{ asset($message->image) }}" alt="Image" width="100">
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    <form id="message-form">
        @csrf
        <div class="mb-3">
            <label class="form-label">Enter Message:</label>
            <textarea name="message" class="form-control" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Send</button>
    </form>
</div>

<!-- Chatbox Auto-Refresh Every 10s -->
<script>
    function formatTimestamp(timestamp) {
        let messageDate = new Date(timestamp);
        let today = new Date();
        
        // If the message is from today, show time only (HH:MM AM/PM)
        if (
            messageDate.getDate() === today.getDate() &&
            messageDate.getMonth() === today.getMonth() &&
            messageDate.getFullYear() === today.getFullYear()
        ) {
            return messageDate.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit", hour12: true });
        } 
        // Otherwise, show only the date (DD MMM YYYY)
        else {
            return messageDate.toLocaleDateString("en-US", { day: "2-digit", month: "short", year: "numeric" });
        }
    }

    function fetchMessages() {
        let chatId = "{{ $chat->id }}";
        let loggedUserId = "{{ auth()->id() }}"; // Get logged-in user ID

        fetch(`/admin/chats/${chatId}/messages`)
            .then(response => response.json())
            .then(data => {
                let chatBox = document.getElementById("chat-box");
                chatBox.innerHTML = ""; // Clear old messages

                data.messages.forEach(message => {
                    let messageDiv = document.createElement("div");

                    let isCurrentUser = message.user_id == loggedUserId;
                    let isAdmin = message.is_admin;

                    messageDiv.classList.add("chat-message");
                    messageDiv.classList.add(isAdmin ? "admin-message" : "user-message");

                    let messageBoxDiv = document.createElement("div");
                    messageBoxDiv.classList.add(isAdmin ? "admin-message-box" : "user-message-box");

                    let username = isAdmin ? "Admin" : (message.user.name ? message.user.name : "User");
                    let timestamp = formatTimestamp(message.created_at); // Format timestamp

                    let imageHtml = '';
                    if (message.image) {
                        imageHtml = `
                            <br>
                            <a href="${message.image}" target="_blank" class="chat-image-link">
                                <img src="${message.image}" alt="Image" class="chat-image" width="100">
                            </a>
                            <br>
                            <a href="${message.image}" download class="btn btn-sm btn-primary mt-2">Download</a>
                        `;
                    }

                    messageBoxDiv.innerHTML = `
                        <p class="username">${username}</p>
                        ${message.message ? `<p class="message-text">${message.message}</p>` : ''} 
                        ${imageHtml}
                        <p class="message-time">${timestamp}</p>
                    `;

                    messageDiv.appendChild(messageBoxDiv);
                    chatBox.appendChild(messageDiv);
                });

                //chatBox.scrollTop = chatBox.scrollHeight; // Auto-scroll to latest message
            })
            .catch(error => console.error("Error fetching messages:", error));
    }

    // Auto-refresh every 10 seconds
    setInterval(fetchMessages, 5000);
    fetchMessages();

    setTimeout(() => {
        let chatBox = document.getElementById("chat-box");
        chatBox.scrollTop = chatBox.scrollHeight;
    }, 2000);

    document.addEventListener("DOMContentLoaded", function () {
        let chatId = "{{ $chat->id }}";
        let messageForm = document.getElementById("message-form");
        let messageInput = document.querySelector("textarea[name='message']");

        messageForm.addEventListener("submit", function (e) {
            e.preventDefault(); // Prevent default form submission

            let messageText = messageInput.value.trim(); // Get message input value

            if (messageText === "") return; // Prevent empty messages

            fetch(`/admin/chats/${chatId}/messages`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                },
                body: JSON.stringify({ message: messageText }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = ""; // Clear message input field after sending
                    fetchMessages(); // Refresh chatbox with the new message
                    
                    setTimeout(() => {
                        let chatBox = document.getElementById("chat-box");
                        chatBox.scrollTop = chatBox.scrollHeight;
                    }, 2000);
                }
            })
            .catch(error => console.error("Error sending message:", error));
        });
    });
</script>

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

<style>
    .chat-message {
        display: flex;
        margin-bottom: 10px;
    }

    .admin-message-box {
        background-color: #f1f1f1;
        padding: 10px;
        border-radius: 10px;
        max-width: 60%;
    }

    .user-message-box {
        background-color: #007bff;
        color: white;
        padding: 10px;
        border-radius: 10px;
        max-width: 60%;
        margin-left: auto; /* Moves user messages to the right */
    }

    .username {
        font-weight: bold;
        margin-bottom: 3px;
        min-width: 100px;
    }

    .message-text {
        margin: 0;
    }

    .chat-container {
        max-height: 300px;  /* Adjust this height if needed */
        overflow-y: auto;   /* Enables vertical scrolling */
        padding: 10px;
        border-radius: 5px;
        background-color: #f8f9fa;
    }
</style>

@endsection
