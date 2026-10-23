@extends('layouts.barbearia')

@section('title', 'Nova Marcação — ' . $barber->name)

@section('content')
<div class="wrap page-body">
    <header class="page-head animate-enter">
        <div>
            <p class="eyebrow">AGENDA DE {{ strtoupper($barber->name) }}</p>
            <h1>Nova Marcação / Walk-in</h1>
            <p class="muted">Regista uma marcação para um cliente com conta ou entrada direta na barbearia.</p>
        </div>
        <div class="actions">
            <a href="{{ route('calendar.show', $barber) }}" class="btn">← Voltar ao calendário</a>
        </div>
    </header>

    <div class="card form-card animate-enter" style="--stagger: 1">
        <div class="barber-pole-strip" aria-hidden="true"></div>

        <form method="POST" action="{{ route('calendar.store', $barber) }}" id="walkin-appointment-form">
            @csrf

            @php
                $currentMode = old('customer_mode', 'existing');
            @endphp

            <fieldset class="form-fieldset">
                <legend class="form-legend">Tipo de cliente</legend>
                <div class="mode-toggle" role="radiogroup" aria-label="Tipo de cliente">
                    <label class="mode-toggle__option {{ $currentMode === 'existing' ? 'is-selected' : '' }}">
                        <input type="radio" name="customer_mode" value="existing"
                               {{ $currentMode === 'existing' ? 'checked' : '' }}
                               data-customer-mode-radio>
                        <span>Já tem conta</span>
                    </label>

                    <label class="mode-toggle__option {{ $currentMode === 'walk_in' ? 'is-selected' : '' }}">
                        <input type="radio" name="customer_mode" value="walk_in"
                               {{ $currentMode === 'walk_in' ? 'checked' : '' }}
                               data-customer-mode-radio>
                        <span>Sem conta (walk-in)</span>
                    </label>
                </div>
                @error('customer_mode')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </fieldset>

            {{-- Secção: Cliente com conta --}}
            <div id="customer-existing-fields" class="form-group" @if($currentMode !== 'existing') hidden @endif>
                <label for="customer_id">Selecionar cliente</label>
                <select name="customer_id" id="customer_id" @if($currentMode !== 'existing') disabled @endif>
                    <option value="">— Escolhe um cliente registado —</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ (int) old('customer_id') === $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }} ({{ $customer->email }})
                        </option>
                    @endforeach
                </select>
                @error('customer_id')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Secção: Cliente Walk-in --}}
            <div id="customer-walkin-fields" class="form-grid-2" @if($currentMode !== 'walk_in') hidden @endif>
                <div class="form-group">
                    <label for="walk_in_name">Nome do cliente (Walk-in)</label>
                    <input type="text" name="walk_in_name" id="walk_in_name"
                           value="{{ old('walk_in_name') }}"
                           placeholder="Ex.: Manuel Ferreira"
                           @if($currentMode !== 'walk_in') disabled @endif>
                    @error('walk_in_name')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="walk_in_phone">Telefone (opcional)</label>
                    <input type="text" name="walk_in_phone" id="walk_in_phone"
                           value="{{ old('walk_in_phone') }}"
                           placeholder="Ex.: 912 345 678"
                           @if($currentMode !== 'walk_in') disabled @endif>
                    @error('walk_in_phone')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Seleção de Serviço habilitado para o barbeiro --}}
            <div class="form-group">
                <label for="service_id">Serviço a realizar</label>
                <select name="service_id" id="service_id" required>
                    <option value="">— Seleciona o serviço —</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}" {{ (int) old('service_id') === $service->id ? 'selected' : '' }}>
                            {{ $service->name }} — {{ $service->duration }} min — {{ number_format((float) $service->price, 2, ',', '.') }} €
                        </option>
                    @endforeach
                </select>
                @error('service_id')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="starts_at">Data e hora de início</label>
                <input type="datetime-local" name="starts_at" id="starts_at"
                       value="{{ old('starts_at', now()->format('Y-m-d\TH:i')) }}" required>
                @error('starts_at')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="notes">Notas / Observações (opcional)</label>
                <textarea name="notes" id="notes" rows="3" placeholder="Preferências de corte, observações...">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary">Guardar marcação</button>
                <a href="{{ route('calendar.show', $barber) }}" class="btn">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection