@extends('layouts.barbearia')

@section('title', 'Disponibilidade & Horários — ' . $barber->name)

@section('content')
@php
    // Resumo rápido para o topo da página
    $activeDays = $workingHours->count();
    $weeklyMinutes = $workingHours->sum(function ($wh) {
        $start = \Carbon\Carbon::createFromFormat('H:i:s', strlen($wh->start_time) === 5 ? $wh->start_time . ':00' : $wh->start_time);
        $end = \Carbon\Carbon::createFromFormat('H:i:s', strlen($wh->end_time) === 5 ? $wh->end_time . ':00' : $wh->end_time);

        return max(0, $start->diffInMinutes($end, true));
    });
    $weeklyHours = intdiv((int) $weeklyMinutes, 60);
    $weeklyRest = (int) $weeklyMinutes % 60;
@endphp

<div class="wrap page-body avail-page">
    <header class="page-head animate-enter">
        <div>
            <p class="eyebrow">GESTÃO PESSOAL DE AGENDA</p>
            <h1>Disponibilidade e Bloqueios</h1>
            <p class="muted">Configura o teu horário semanal de trabalho e regista férias, consultas ou ausências.</p>
        </div>
        <div class="actions">
            <a href="{{ route('barber.dashboard') }}" class="btn ghost">← Voltar ao Dashboard</a>
        </div>
    </header>

    {{-- Resumo --}}
    <section class="avail-stats animate-enter" style="--stagger: 1" aria-label="Resumo da semana">
        <div class="avail-stat avail-stat--red">
            <span class="avail-stat__icon" aria-hidden="true">✂</span>
            <div>
                <strong>{{ $activeDays }}<small>/7</small></strong>
                <span>dias de trabalho</span>
            </div>
        </div>
        <div class="avail-stat avail-stat--sky">
            <span class="avail-stat__icon" aria-hidden="true">⏱</span>
            <div>
                <strong>{{ $weeklyHours }}h@if($weeklyRest > 0){{ str_pad($weeklyRest, 2, '0', STR_PAD_LEFT) }}@endif</strong>
                <span>por semana</span>
            </div>
        </div>
        <div class="avail-stat avail-stat--ink">
            <span class="avail-stat__icon" aria-hidden="true">🌴</span>
            <div>
                <strong>{{ $blocks->count() }}</strong>
                <span>{{ $blocks->count() === 1 ? 'bloqueio agendado' : 'bloqueios agendados' }}</span>
            </div>
        </div>
    </section>

    <div class="avail-layout">
        {{-- Secção 1: Horário Semanal --}}
        <section class="avail-card animate-enter" style="--stagger: 2">
            <div class="avail-card__head">
                <span class="avail-card__badge avail-card__badge--red" aria-hidden="true">📅</span>
                <div>
                    <h2>Horário Semanal</h2>
                    <p class="muted">Liga os dias em que trabalhas e define o intervalo de atendimento.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('barber.availability.hours') }}" class="avail-form">
                @csrf
                @method('PUT')

                <div class="day-list">
                    @foreach($daysOfWeek as $dayNum => $dayLabel)
                        @php
                            $wh = $workingHours->get($dayNum);
                            $isActive = $wh !== null;
                            $startVal = $wh ? substr($wh->start_time, 0, 5) : '09:00';
                            $endVal = $wh ? substr($wh->end_time, 0, 5) : '18:00';
                        @endphp
                        <div class="day-row {{ $isActive ? 'is-on' : '' }}" data-day-row>
                            <label class="day-switch">
                                <input type="checkbox"
                                       name="days[{{ $dayNum }}][active]"
                                       value="1"
                                       {{ $isActive ? 'checked' : '' }}
                                       data-day-toggle>
                                <span class="day-switch__track" aria-hidden="true">
                                    <span class="day-switch__thumb"></span>
                                </span>
                                <span class="day-switch__name">{{ $dayLabel }}</span>
                            </label>

                            <div class="day-times" data-day-times>
                                <label class="sr-only" for="start_{{ $dayNum }}">Início {{ $dayLabel }}</label>
                                <input type="time"
                                       id="start_{{ $dayNum }}"
                                       name="days[{{ $dayNum }}][start_time]"
                                       value="{{ old("days.$dayNum.start_time", $startVal) }}"
                                       {{ !$isActive ? 'disabled' : '' }}>
                                <span class="day-times__sep" aria-hidden="true">→</span>
                                <label class="sr-only" for="end_{{ $dayNum }}">Fim {{ $dayLabel }}</label>
                                <input type="time"
                                       id="end_{{ $dayNum }}"
                                       name="days[{{ $dayNum }}][end_time]"
                                       value="{{ old("days.$dayNum.end_time", $endVal) }}"
                                       {{ !$isActive ? 'disabled' : '' }}>
                            </div>

                            <span class="day-row__off" aria-hidden="true">Folga</span>
                        </div>
                    @endforeach
                </div>

                <div class="avail-actions">
                    <button type="submit" class="btn">Guardar horário semanal</button>
                </div>
            </form>
        </section>

        {{-- Secção 2: Bloqueios / Indisponibilidades --}}
        <section class="avail-col animate-enter" style="--stagger: 3">
            <div class="avail-card avail-card--sky">
                <div class="avail-card__head">
                    <span class="avail-card__badge avail-card__badge--sky" aria-hidden="true">🚫</span>
                    <div>
                        <h2>Novo Bloqueio / Ausência</h2>
                        <p class="muted">Férias, consulta médica ou bloqueio parcial de horas.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('barber.availability.blocks.store') }}" id="schedule-block-form" class="avail-form">
                    @csrf

                    <div class="avail-field">
                        <label for="block_date">Data</label>
                        <input type="date" name="date" id="block_date"
                               value="{{ old('date', now()->format('Y-m-d')) }}"
                               min="{{ now()->format('Y-m-d') }}" required>
                        @error('date')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="avail-chip" for="block_all_day">
                        <input type="checkbox" name="all_day" id="block_all_day" value="1"
                               {{ old('all_day') ? 'checked' : '' }}
                               data-all-day-toggle>
                        <span class="avail-chip__box" aria-hidden="true"></span>
                        <span>Dia inteiro <small>(00:00 — 23:59)</small></span>
                    </label>

                    <div class="avail-two" id="block-time-fields" @if(old('all_day')) hidden @endif>
                        <div class="avail-field">
                            <label for="block_start_time">Hora inicial</label>
                            <input type="time" name="start_time" id="block_start_time"
                                   value="{{ old('start_time', '14:00') }}"
                                   @if(old('all_day')) disabled @endif>
                            @error('start_time')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="avail-field">
                            <label for="block_end_time">Hora final</label>
                            <input type="time" name="end_time" id="block_end_time"
                                   value="{{ old('end_time', '16:00') }}"
                                   @if(old('all_day')) disabled @endif>
                            @error('end_time')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="avail-field">
                        <label for="block_reason">Motivo</label>
                        <input type="text" name="reason" id="block_reason"
                               value="{{ old('reason') }}"
                               placeholder="Ex.: Férias, Consulta, Ausência..."
                               maxlength="140">
                        @error('reason')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="avail-actions">
                        <button type="submit" class="btn">+ Adicionar bloqueio</button>
                    </div>
                </form>
            </div>

            {{-- Lista de próximos bloqueios --}}
            <div class="avail-card avail-card--plain">
                <div class="avail-card__head">
                    <span class="avail-card__badge avail-card__badge--ink" aria-hidden="true">🗓</span>
                    <div>
                        <h2>Próximos bloqueios</h2>
                        <p class="muted">O que já está marcado como indisponível.</p>
                    </div>
                </div>

                @if($blocks->isEmpty())
                    <div class="avail-empty">
                        <span aria-hidden="true">✨</span>
                        <p>Sem bloqueios ou ausências futuras. Agenda toda livre!</p>
                    </div>
                @else
                    <div class="blocks-list">
                        @foreach($blocks as $block)
                            @php
                                $isFullDay = substr($block->start_time, 0, 5) === '00:00' && substr($block->end_time, 0, 5) === '23:59';
                                $blockDate = \Carbon\Carbon::parse($block->date);
                            @endphp
                            <article class="block-item">
                                <div class="block-item__date" aria-hidden="true">
                                    <strong>{{ $blockDate->format('d') }}</strong>
                                    <span>{{ $blockDate->translatedFormat('M') }}</span>
                                </div>
                                <div class="block-item__body">
                                    <p class="block-item__reason">{{ $block->reason ?: 'Indisponibilidade' }}</p>
                                    <p class="block-item__meta">
                                        {{ $blockDate->format('d/m/Y') }}
                                        <span class="badge">
                                            {{ $isFullDay ? 'Dia inteiro' : substr($block->start_time, 0, 5) . ' — ' . substr($block->end_time, 0, 5) }}
                                        </span>
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('barber.availability.blocks.destroy', $block) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn small ghost" aria-label="Remover bloqueio de {{ $blockDate->format('d/m/Y') }}">Remover</button>
                                </form>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
