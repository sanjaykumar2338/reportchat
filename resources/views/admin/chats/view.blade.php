@extends('layouts.admin')

@section('content')
@include('layouts.sidebar')

<div class="container mt-4" style="width: 92%; margin-left: 176px;">
    <h2>Reporte: {{ $chat->title }}</h2>
    <p><strong>Ubicación:</strong> {{ $chat->location }}</p>

    <p><strong>Estado:</strong> 
    <span id="status-badge" class="badge bg-{{ 
    ($chat->status == 'pending' ? 'warning' : 
    ($chat->status == 'solved' ? 'success' : 
    ($chat->status == 'refused' ? 'danger' : 'secondary'))) }}">
    {{ $chat->status == 'pending' ? 'Pendiente' : ($chat->status == 'solved' ? 'Resuelto' : ($chat->status == 'refused' ? 'Rechazado' : ucfirst($chat->status))) }}
    </span>

    </p>

    <!-- Lista Desplegable de Estado -->
    <div class="mb-3">
        <label for="status" class="form-label"><strong>Cambiar Estado:</strong></label>
        <select id="status" class="form-control" data-chat-id="{{ $chat->id }}">
            <option value="pending" {{ $chat->status == 'pending' ? 'selected' : '' }}>Pendiente</option>
            <option value="refused" {{ $chat->status == 'refused' ? 'selected' : '' }}>Rechazado</option>
            <option value="solved" {{ $chat->status == 'solved' ? 'selected' : '' }}>Resuelto</option>
        </select>
    </div>

    <h4>Mensajes</h4>
    <div class="border p-3 mb-3 chat-container" id="chat-box">
        @foreach($chat->messages as $message)
            <div class="chat-message {{ $message->is_admin ? 'admin-message' : 'user-message' }}">
                <div class="{{ $message->is_admin ? 'admin-message-box' : 'user-message-box' }}">
                    <p class="username">{{ $message->is_admin ? 'Administrador' : $message->user->name }}</p>
                    <p class="message-text">{{ $message->message }}</p>
                    @if($message->image)
                        <br><img src="{{ asset($message->image) }}" alt="Imagen" width="100">
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    <form id="message-form">
        @csrf
        <div class="mb-3">
            <label class="form-label">Escribir mensaje:</label>
            <textarea name="message" class="form-control" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Enviar</button>
    </form>
</div>

<!-- Chatbox Auto-Refresh Every 10s -->
<script>
    function formatTimestamp(timestamp) {
        let messageDate = new Date(timestamp);
        let today = new Date();
        
        if (
            messageDate.getDate() === today.getDate() &&
            messageDate.getMonth() === today.getMonth() &&
            messageDate.getFullYear() === today.getFullYear()
        ) {
            return messageDate.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit", hour12: true });
        } 
        else {
            return messageDate.toLocaleDateString("es-ES", { day: "2-digit", month: "short", year: "numeric" });
        }
    }

    function formatMessage(text) {
        if (!text) {
            return '';
        }
        const urlRegex = /(https?:\/\/\S+\.(?:jpg|jpeg|png|gif|webp|svg))/gi;

        return text.replace(urlRegex, (url) => {
            return `
                <a href="${url}" target="_blank" class="chat-image-link" title="Abrir imagen en nueva pestaña">
                    <img src="${url}" alt="Imagen desde URL" class="chat-image" style="max-width: 250px; border-radius: 8px; margin-top: 5px; display: block;">
                </a>
            `;
        });
    }

    function fetchMessages() {
        let chatId = "{{ $chat->id }}";
        let loggedUserId = "{{ auth()->id() }}";

        fetch(`/admin/chats/${chatId}/messages`)
            .then(response => response.json())
            .then(data => {
                let chatBox = document.getElementById("chat-box");
                chatBox.innerHTML = "";

                data.messages.forEach(message => {
                    let messageDiv = document.createElement("div");

                    let isCurrentUser = message.user_id == loggedUserId;
                    let isAdmin = message.is_admin;

                    messageDiv.classList.add("chat-message");
                    messageDiv.classList.add(isAdmin ? "admin-message" : "user-message");

                    let messageBoxDiv = document.createElement("div");
                    messageBoxDiv.classList.add(isAdmin ? "admin-message-box" : "user-message-box");

                    let username = isAdmin ? "Administrador" : (message.user.name ? message.user.name : "Usuario");
                    let timestamp = formatTimestamp(message.created_at);

                    let imageHtml = '';
                    if (message.image) {
                        imageHtml = `
                            <br>
                            <a href="${message.image}" target="_blank" class="chat-image-link">
                                <img src="${message.image}" alt="Imagen" class="chat-image" width="100">
                            </a>
                            <br>
                            <a style="display:none;" href="${message.image}" download class="btn btn-sm btn-primary mt-2">Descargar</a>
                        `;
                    }

                    messageBoxDiv.innerHTML = `
                        <p class="username">${username}</p>
                        ${!message.image && message.message ? `<p class="message-text">${message.message}</p>` : ''}
                        ${imageHtml}
                        <p class="message-time">${timestamp}</p>
                    `;

                    messageDiv.appendChild(messageBoxDiv);
                    chatBox.appendChild(messageDiv);
                });
            })
            .catch(error => console.error("Error al obtener mensajes:", error));
    }

    setInterval(fetchMessages, 5000);
    fetchMessages();
    
    takemeup();
    function takemeup(){
        setTimeout(() => {
            let chatBox = document.getElementById("chat-box");
            chatBox.scrollTop = chatBox.scrollHeight;
        }, 2000);
    }

    document.addEventListener("DOMContentLoaded", function () {
        let chatId = "{{ $chat->id }}";
        let messageForm = document.getElementById("message-form");
        let messageInput = document.querySelector("textarea[name='message']");

        messageForm.addEventListener("submit", function (e) {
            e.preventDefault();

            let messageText = messageInput.value.trim();

            if (messageText === "") return;

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
                    messageInput.value = "";
                    fetchMessages();
                    takemeup();
                }
            })
            .catch(error => console.error("Error al enviar mensaje:", error));
        });
    });
</script>

<!-- AJAX para Actualizar Estado -->
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
                    statusBadge.innerText = newStatus === "pending" ? "Pendiente" : (newStatus === "solved" ? "Resuelto" : (newStatus === "refused" ? "Rechazado" : newStatus));
                    
                    statusBadge.className = "badge bg-" + 
                        (newStatus === "solved" ? "success" :
                        (newStatus === "pending" ? "warning" :
                        (newStatus === "refused" ? "danger" : "secondary")));
                }
            })
            .catch(error => console.error("Error al actualizar estado:", error));
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
        margin-left: auto;
    }

    .username {
        font-weight: bold;
        margin-bottom: 3px;
        min-width: 100px;
    }

    .message-text {
        margin: 0;
        font-size: 18px;
    }

    .chat-container {
        max-height: 300px;
        overflow-y: auto;
        padding: 10px;
        border-radius: 5px;
        background-color: #f8f9fa;
    }
</style>

@endsection
