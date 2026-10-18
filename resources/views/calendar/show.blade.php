@extends('layouts.barbearia')

@section('title', 'Calendário')

@section('content')
<div class="wrap">
    <header class="page-head">
        <div>
            <h1>Calendário</h1>
            <p class="sub">
                @if ($readOnly)
                    Agenda de {{ $barber->name }}.
                @else
                    A tua agenda.
                @endif
            </p>
        </div>

        @unless ($readOnly)
            <a class="btn" href="{{ route('calendar.appointments.create') }}">Nova marcação</a>
        @endunless
    </header>

    <div class="page-body">
        @include('partials.messages')

        <nav class="calendar-nav" aria-label="Navegação de mês">
            <a
                class="btn small ghost"
                href="{{ $readOnly
                    ? route('barbers.calendar', ['barber' => $barber->id, 'month' => $prevMonth])
                    : route('calendar.mine', ['month' => $prevMonth]) }}"
            >&larr; Mês anterior</a>

            <strong class="calendar-month-label">{{ ucfirst($month->translatedFormat('F \d\e Y')) }}</strong>

            <a
                class="btn small ghost"
                href="{{ $readOnly
                    ? route('barbers.calendar', ['barber' => $barber->id, 'month' => $nextMonth])
                    : route('calendar.mine', ['month' => $nextMonth]) }}"
            >Mês seguinte &rarr;</a>
        </nav>

        <div class="calendar">
            <div class="calendar-weekday-row">
                <span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span><span>Dom</span>
            </div>

            @foreach ($weeks as $week)
                <div class="calendar-week">
                    @foreach ($week as $day)
                        <div class="calendar-day @if (! $day['inMonth']) is-outside @endif @if ($day['isToday']) is-today @endif">
                            <span class="calendar-day-number">{{ $day['date']->day }}</span>

                            @foreach ($day['appointments'] as $appointment)
                                <a
                                    class="calendar-event status-{{ $appointment->status?->value }}"
                                    href="{{ route('appointments.show', $appointment->id) }}"
                                >
                                    <span class="calendar-event-time">{{ substr($appointment->time, 0, 5) }}</span>
                                    <span class="calendar-event-name">{{ $appointment->customerDisplayName() }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection