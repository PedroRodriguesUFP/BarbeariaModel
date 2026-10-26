@extends('layouts.barbearia')

@section('title', 'Calendário — ' . $barber->name)

@section('content')
@php
    $weekdays = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];

    // O admin vê o calendário de um barbeiro; o barbeiro vê o seu
    if ($readOnly) {
        $calendarUrl = route('barbers.calendar', $barber);
    } else {
        $calendarUrl = route('calendar.mine');
    }
@endphp

<div class="wrap page-body">
    <header class="page-head animate-enter">
        <div>
            <p class="eyebrow">AGENDA MENSAL · BARBEIRO</p>
            <h1>{{ $barber->name }} — {{ ucfirst($month->translatedFormat('F Y')) }}</h1>
        </div>
        <div class="actions">
            <a href="{{ $calendarUrl }}?month={{ $prevMonth }}" class="btn">← Mês anterior</a>
            <a href="{{ $calendarUrl }}?month={{ now()->format('Y-m') }}" class="btn">Hoje</a>
            <a href="{{ $calendarUrl }}?month={{ $nextMonth }}" class="btn">Mês seguinte →</a>

            @if(! $readOnly)
                <a href="{{ route('calendar.appointments.create') }}" class="btn btn-primary">+ Nova Marcação / Walk-in</a>
            @endif
        </div>
    </header>

    <div class="calendar-legend card animate-enter" style="--stagger: 1" aria-label="Legenda de estados">
        <span class="calendar-legend__title">Estados:</span>
        <span class="calendar-legend__item"><i class="status-dot status-dot--pending"></i> Pendente</span>
        <span class="calendar-legend__item"><i class="status-dot status-dot--confirmed"></i> Confirmada</span>
        <span class="calendar-legend__item"><i class="status-dot status-dot--completed"></i> Concluída</span>
        <span class="calendar-legend__item"><i class="status-dot status-dot--cancelled"></i> Cancelada</span>
    </div>

    <div class="calendar-board card animate-enter" style="--stagger: 2">
        <div class="calendar-weekdays" aria-hidden="true">
            @foreach($weekdays as $weekday)
                <div class="calendar-weekday">{{ $weekday }}</div>
            @endforeach
        </div>

        <div class="calendar-grid">
            @foreach($weeks as $week)
                @foreach($week as $day)
                    @php
                        $cellClass = 'calendar-cell';

                        if (! $day['inMonth']) {
                            $cellClass .= ' is-outside';
                        }

                        if ($day['isToday']) {
                            $cellClass .= ' is-today';
                        }

                        if ($day['appointments']->isEmpty()) {
                            $cellClass .= ' is-empty';
                        }
                    @endphp

                    <div class="{{ $cellClass }}">
                        <div class="calendar-cell__header">
                            <span class="calendar-cell__dayname">{{ $day['date']->translatedFormat('D') }}</span>
                            <span class="calendar-cell__number">
                                {{ $day['date']->format('d') }}
                                @if($day['isToday'])
                                    <span class="today-marker" title="Hoje">Hoje</span>
                                @endif
                            </span>
                        </div>

                        <div class="calendar-cell__events">
                            @if($day['appointments']->isEmpty())
                                <span class="calendar-cell__none">Sem marcações</span>
                            @else
                                @foreach($day['appointments'] as $appointment)
                                    <a href="{{ route('appointments.show', $appointment) }}"
                                       class="calendar-event calendar-event--{{ $appointment->status->value }}">
                                        <div class="calendar-event__top">
                                            <span class="calendar-event__time">
                                                @if($appointment->time)
                                                    {{ substr($appointment->time, 0, 5) }}
                                                @else
                                                    --:--
                                                @endif
                                            </span>
                                            <span class="status-dot status-dot--{{ $appointment->status->value }}" aria-hidden="true"></span>
                                        </div>
                                        <div class="calendar-event__client">{{ $appointment->customerDisplayName() }}</div>
                                        <div class="calendar-event__service">{{ $appointment->service->name }}</div>
                                    </a>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
</div>
@endsection