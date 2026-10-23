@extends('layouts.barbearia')

@section('title', 'Calendário — ' . $barber->name)

@section('content')
@php
    $prevMonth = (clone $currentMonth)->subMonth()->format('Y-m');$nextMonth = (clone $currentMonth)->addMonth()->format('Y-m');$weekdays = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
@endphp

<div class="wrap page-body">
    <header class="page-head animate-enter">
        <div>
            <p class="eyebrow">AGENDA MENSAL · BARBEIRO</p>
            <h1>{{ $barber->name }} — {{ ucfirst($currentMonth->translatedFormat('F Y')) }}</h1>
        </div>
        <div class="actions">
            <a href="{{ route('calendar.show', [$barber, 'month' =>$prevMonth]) }}" class="btn">← Mês anterior</a>
            <a href="{{ route('calendar.show', [$barber, 'month' => now()->format('Y-m')]) }}" class="btn">Hoje</a>
            <a href="{{ route('calendar.show', [$barber, 'month' =>$nextMonth]) }}" class="btn">Mês seguinte →</a>
            @if(auth()->check() && (auth()->id() === $barber->id || auth()->user()->isAdmin()))
                <a href="{{ route('calendar.create', $barber) }}" class="btn btn-primary">+ Nova Marcação / Walk-in</a>
            @endif
        </div>
    </header>

    {{-- Legenda editorial de estados --}}
    <div class="calendar-legend card animate-enter" style="--stagger: 1" aria-label="Legenda de estados">
        <span class="calendar-legend__title">Estados:</span>
        <span class="calendar-legend__item"><i class="status-dot status-dot--pending"></i> Pendente</span>
        <span class="calendar-legend__item"><i class="status-dot status-dot--confirmed"></i> Confirmada</span>
        <span class="calendar-legend__item"><i class="status-dot status-dot--completed"></i> Concluída</span>
        <span class="calendar-legend__item"><i class="status-dot status-dot--cancelled"></i> Cancelada</span>
    </div>

    <div class="calendar-board card animate-enter" style="--stagger: 2">
        <div class="calendar-weekdays" aria-hidden="true">
            @foreach($weekdays as$dayName)
                <div class="calendar-weekday">{{ $dayName }}</div>
            @endforeach
        </div>

        <div class="calendar-grid">
            @for($day = (clone $startGrid);$day->lte($endGrid);$day->addDay())
                @php
                    $dateKey = $day->format('Y-m-d');$isCurrentMonth = $day->month ===$currentMonth->month;
                    $isToday = $day->isToday();$dayAppointments = $appointmentsByDate[$dateKey] ?? collect();
                @endphp

                <div class="calendar-cell {{ !$isCurrentMonth ? 'is-outside' : '' }} {{ $isToday ? 'is-today' : '' }} {{ $dayAppointments->isEmpty() ? 'is-empty' : '' }}">
                    <div class="calendar-cell__header">
                        <span class="calendar-cell__dayname">{{ $day->translatedFormat('D') }}</span>
                        <span class="calendar-cell__number">
                            {{ $day->format('d') }}
                            @if($isToday)
                                <span class="today-marker" title="Hoje">Hoje</span>
                            @endif
                        </span>
                    </div>

                    <div class="calendar-cell__events">
                        @forelse($dayAppointments as$appointment)
                            <a href="{{ route('appointments.show', $appointment) }}"
                               class="calendar-event calendar-event--{{ $appointment->status->value }}">
                                <div class="calendar-event__top">
                                    <span class="calendar-event__time">{{ $appointment->starts_at->format('H:i') }}</span>
                                    <span class="status-dot status-dot--{{ $appointment->status->value }}" aria-hidden="true"></span>
                                </div>
                                <div class="calendar-event__client">{{ $appointment->customerDisplayName() }}</div>
                                @if($appointment->service)
                                    <div class="calendar-event__service">{{ $appointment->service->name }}</div>
                                @endif
                            </a>
                        @empty
                            <span class="calendar-cell__none">Sem marcações</span>
                        @endforelse
                    </div>
                </div>
            @endfor
        </div>
    </div>
</div>
@endsection