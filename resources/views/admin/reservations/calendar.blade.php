<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
@extends('layouts.admin')

@section('content')
@include('layouts.sidebar')

<div class="container mt-4" style="margin-left: 176px; width: 92%;">
    <form method="GET" action="{{ route('admin.reservations.calendar') }}" class="mb-3">
        <div class="row">
            <div class="col-md-3">
                <select name="user_id" class="form-control">
                    <option value="">Todos los Usuarios</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <select name="room_id" class="form-control">
                    <option value="">Todas las Salas</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>
                            {{ $room->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 d-flex">
                <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                <a href="{{ route('admin.reservations.calendar') }}" class="btn btn-secondary">Reiniciar</a>
            </div>
        </div>
    </form>

    <h2>Calendario de Reservas</h2>
    <div id='calendar'></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'es',
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: {
                url: '{{ route('admin.reservations.events') }}',
                method: 'GET',
                extraParams: {
                    user_id: '{{ request('user_id') }}',
                    room_id: '{{ request('room_id') }}'
                }
            },
            eventClick: function (info) {
                info.jsEvent.preventDefault();
                if (info.event.url) {
                    window.location.href = info.event.url;
                }
            }
        });
        calendar.render();
    });
</script>
@endsection