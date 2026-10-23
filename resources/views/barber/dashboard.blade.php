@extends('layouts.barbearia')

@section('title', 'Bancada do Barbeiro — ' . $barber->name)

@section('content')
<div class="wrap page-body">
    <div class="barber-pole-strip" aria-hidden="true"></div>

    <header class="page-head barber-dash-head animate-enter">
        <div>
            <p class="eyebrow">ÁREA DE TRABALHO · {{ now()->translatedFormat('d \d\e F, Y') }}</p>
            <h1>{{ $greeting }}, {{$barber->name }}</h1>
            <p class="muted">Aqui tens a tua agenda e o estado do dia.</p>
        </div>
        <div class="actions">
            <a href="{{ route('calendar.show', $barber) }}" class="btn">Ver calendário</a>
            <a href="{{ route('calendar.create', $barber) }}" class="btn btn-primary">+ Adicionar marcação</a>
        </div>
    </header>

    {{-- Resumo do dia (Painéis físicos com data-tilt) --}}
    <section aria-label="Resumo do dia" class="barber-stats-grid">
        <article class="card stat-placard animate-enter" data-tilt style="--stagger: 1">
            <span class="stat-placard__label lift">Marcações de hoje</span>
            <strong class="stat-placard__value lift-far">{{ $stats['today'] }}</strong>
            <span class="stat-placard__foot">Agenda diária</span>
        </article>

        <article class="card stat-placard animate-enter" data-tilt style="--stagger: 2">
            <span class="stat-placard__label lift">Próximas marcações</span>
            <strong class="stat-placard__value lift-far">{{ $stats['upcoming'] }}</strong>
            <span class="stat-placard__foot">Confirmadas / Pendentes</span>
        </article>

        <article class="card stat-placard animate-enter" data-tilt style="--stagger: 3">
            <span class="stat-placard__label lift">Concluídas hoje</span>
            <strong class="stat-placard__value lift-far">{{ $stats['completed'] }}</strong>
            <span class="stat-placard__foot">Serviços finalizados</span>
        </article>

        <article class="card stat-placard {{ $stats['pending'] > 0 ? 'stat-placard--alert' : '' }} animate-enter" data-tilt style="--stagger: 4">
            <span class="stat-placard__label lift">Pendentes hoje</span>
            <strong class="stat-placard__value lift-far">{{ $stats['pending'] }}</strong>
            <span class="stat-placard__foot">A aguardar confirmação</span>
        </article>
    </section>

    <div class="barber-work-grid">
        {{-- Destaque: Próxima Marcação --}}
        <section class="barber-next-col animate-enter" style="--stagger: 5">
            <div class="card next-appointment-card" data-tilt>
                <div class="barber-pole-strip" aria-hidden="true"></div>
                <div class="next-appointment-card__head">
                    <span class="eyebrow">PRÓXIMO CLIENTE NA CADEIRA</span>
                    @if($nextAppointment)
                        <span class="badge badge--{{ $nextAppointment->status->value }}">
                            {{ ucfirst($nextAppointment->status->value) }}
                        </span>
                    @endif
                </div>

                @if($nextAppointment)
                    <div class="next-appointment-card__time lift-far">
                        {{ $nextAppointment->starts_at->format('H:i') }}
                        <small>{{ $nextAppointment->starts_at->isToday() ? 'Hoje' :$nextAppointment->starts_at->format('d/m') }}</small>
                    </div>

                    <h2 class="next-appointment-card__client lift">
                        {{ $nextAppointment->customerDisplayName() }}
                        @if($nextAppointment->isWalkIn())
                            <span class="badge">Walk-in</span>
                        @endif
                    </h2>

                    <dl class="detail next-appointment-card__details">
                        <div>
                            <dt>Serviço</dt>
                            <dd>{{ $nextAppointment->service?->name ?? 'Serviço' }}</dd>
                        </div>
                        <div>
                            <dt>Duração</dt>
                            <dd>{{ $nextAppointment->service?->duration ?? 30 }} min</dd>
                        </div>
                    </dl>

                    <div class="actions">
                        <a href="{{ route('appointments.show', $nextAppointment) }}" class="btn btn-primary">
                            Abrir ficha da marcação
                        </a>
                    </div>
                @else
                    <div class="empty-state">
                        <p><strong>Sem próximas marcações agendadas.</strong></p>
                        <p class="muted">Podes registar um cliente walk-in ou consultar o calendário mensal.</p>
                        <div class="actions">
                            <a href="{{ route('calendar.create', $barber) }}" class="btn btn-primary">+ Registar Walk-in</a>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        {{-- Agenda de Hoje (Timeline Vertical) --}}
        <section class="barber-agenda-col animate-enter" style="--stagger: 6">
            <div class="card agenda-panel">
                <header class="agenda-panel__head">
                    <h2>Agenda de hoje</h2>
                    <span class="badge">{{ $todayAppointments->count() }} {{$todayAppointments->count() === 1 ? 'marcação' : 'marcações' }}</span>
                </header>

                @if($todayAppointments->isEmpty())
                    <div class="empty-state">
                        <p class="muted">Não tens marcações registadas para o dia de hoje.</p>
                    </div>
                @else
                    <ol class="barber-timeline">
                        @foreach($todayAppointments as$appt)
                            <li class="barber-timeline__item status--{{ $appt->status->value }}">
                                <div class="barber-timeline__time">
                                    {{ $appt->starts_at->format('H:i') }}
                                </div>
                                <div class="barber-timeline__marker" aria-hidden="true"></div>
                                <a href="{{ route('appointments.show', $appt) }}" class="barber-timeline__content">
                                    <div class="barber-timeline__top">
                                        <strong>
                                            @if($appt->isWalkIn())
                                                Walk-in — {{ $appt->customerDisplayName() }}
                                            @else
                                                {{ $appt->customerDisplayName() }}
                                            @endif
                                        </strong>
                                        <span class="badge badge--{{ $appt->status->value }}">
                                            @switch($appt->status->value)
                                                @case('pending') Pendente @break
                                                @case('confirmed') Confirmada @break
                                                @case('completed') Concluída @break
                                                @case('cancelled') Cancelada @break
                                                @default {{ $appt->status->value }}
                                            @endswitch
                                        </span>
                                    </div>
                                    <div class="barber-timeline__sub">
                                        <span>{{ $appt->service?->name ?? 'Serviço' }}</span>
                                        <span>· {{ $appt->service?->duration ?? 30 }} min</span>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection