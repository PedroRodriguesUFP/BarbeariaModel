@extends('layouts.barbearia')

@section('title', 'Disponibilidade & Horários — ' . $barber->name)

@section('content')
<div class="wrap page-body">
    <header class="page-head animate-enter">
        <div>
            <p class="eyebrow">GESTÃO PESSOAL DE AGENDA</p>
            <h1>Disponibilidade e Bloqueios</h1>
            <p class="muted">Configura o teu horário semanal de trabalho e regista férias, consultas ou ausências.</p>
        </div>
        <div class="actions">
            <a href="{{ route('barber.dashboard') }}" class="btn">← Voltar ao Dashboard</a>
        </div>
    </header>

    <div class="availability-layout">
        {{-- Secção 1: Horário Semanal --}}
        <section class="card form-card animate-enter" style="--stagger: 1">
            <div class="barber-pole-strip" aria-hidden="true"></div>
            <h2>Horário Semanal</h2>
            <p class="muted">Define os dias ativos e o intervalo de atendimento habitual.</p>

            <form method="POST" action="{{ route('barber.availability.hours') }}">
                @csrf
                @method('PUT')

                <div class="working-hours-list">
                    @foreach($daysOfWeek as $dayNum => $dayLabel)
                        @php
                            $wh = $workingHours->get($dayNum);
                            $isActive = $wh !== null;
                            $startVal = $wh ? substr($wh->start_time, 0, 5) : '09:00';
                            $endVal = $wh ? substr($wh->end_time, 0, 5) : '18:00';
                        @endphp
                        <div class="working-hours-row">
                            <label class="working-hours-row__day">
                                <input type="checkbox"
                                       name="days[{{ $dayNum }}][active]"
                                       value="1"
                                       {{ $isActive ? 'checked' : '' }}
                                       data-day-toggle>
                                <strong>{{ $dayLabel }}</strong>
                            </label>

                            <div class="working-hours-row__times" data-day-times>
                                <label class="sr-only" for="start_{{ $dayNum }}">Início {{ $dayLabel }}</label>
                                <input type="time"
                                       id="start_{{ $dayNum }}"
                                       name="days[{{ $dayNum }}][start_time]"
                                       value="{{ old("days.$dayNum.start_time", $startVal) }}"
                                       {{ !$isActive ? 'disabled' : '' }}>
                                <span>—</span>
                                <label class="sr-only" for="end_{{ $dayNum }}">Fim {{ $dayLabel }}</label>
                                <input type="time"
                                       id="end_{{ $dayNum }}"
                                       name="days[{{ $dayNum }}][end_time]"
                                       value="{{ old("days.$dayNum.end_time", $endVal) }}"
                                       {{ !$isActive ? 'disabled' : '' }}>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="actions" style="margin-top: 1.25rem;">
                    <button type="submit" class="btn btn-primary">Guardar horário semanal</button>
                </div>
            </form>
        </section>

        {{-- Secção 2: Bloqueios / Indisponibilidades --}}
        <section class="availability-blocks-col animate-enter" style="--stagger: 2">
            <div class="card form-card">
                <h2>Novo Bloqueio / Ausência</h2>
                <p class="muted">Férias, consulta médica ou bloqueio parcial de horas.</p>

                <form method="POST" action="{{ route('barber.availability.blocks.store') }}" id="schedule-block-form">
                    @csrf

                    <div class="form-group">
                        <label for="block_date">Data</label>
                        <input type="date" name="date" id="block_date"
                               value="{{ old('date', now()->format('Y-m-d')) }}"
                               min="{{ now()->format('Y-m-d') }}" required>
                        @error('date')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="checkbox-inline" for="block_all_day">
                            <input type="checkbox" name="all_day" id="block_all_day" value="1"
                                   {{ old('all_day') ? 'checked' : '' }}
                                   data-all-day-toggle>
                            <span>Dia inteiro (00:00 — 23:59)</span>
                        </label>
                    </div>

                    <div class="form-grid-2" id="block-time-fields" @if(old('all_day')) hidden @endif>
                        <div class="form-group">
                            <label for="block_start_time">Hora inicial</label>
                            <input type="time" name="start_time" id="block_start_time"
                                   value="{{ old('start_time', '14:00') }}"
                                   @if(old('all_day')) disabled @endif>
                            @error('start_time')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="block_end_time">Hora final</label>
                            <input type="time" name="end_time" id="block_end_time"
                                   value="{{ old('end_time', '16:00') }}"
                                   @if(old('all_day')) disabled @endif>
                            @error('end_time')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="block_reason">Motivo</label>
                        <input type="text" name="reason" id="block_reason"
                               value="{{ old('reason') }}"
                               placeholder="Ex.: Férias, Consulta, Ausência..."
                               maxlength="140">
                        @error('reason')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="actions">
                        <button type="submit" class="btn btn-primary">+ Adicionar bloqueio</button>
                    </div>
                </form>
            </div>

            {{-- Lista de próximos bloqueios --}}
            <div class="card" style="margin-top: 1.5rem;">
                <h3>Próximos bloqueios agendados</h3>
                @if($blocks->isEmpty())
                    <p class="muted" style="margin-top: 0.5rem;">Sem bloqueios ou ausências futuras registadas.</p>
                @else
                    <div class="blocks-list">
                        @foreach($blocks as $block)
                            @php
                                $isFullDay = substr($block->start_time, 0, 5) === '00:00' && substr($block->end_time, 0, 5) === '23:59';
                            @endphp
                            <article class="block-item">
                                <div>
                                    <strong>{{ \Carbon\Carbon::parse($block->date)->format('d/m/Y') }}</strong>
                                    <span class="badge">
                                        {{ $isFullDay ? 'Dia inteiro' : substr($block->start_time, 0, 5) . ' — ' . substr($block->end_time, 0, 5) }}
                                    </span>
                                    <p class="muted">{{ $block->reason ?: 'Indisponibilidade' }}</p>
                                </div>
                                <form method="POST" action="{{ route('barber.availability.blocks.destroy', $block) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm">Remover</button>
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