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
            <a href="{{ route('calendar.mine') }}" class="btn">← Voltar ao calendário</a>
        </div>
    </header>

    <div class="card form-card animate-enter" style="--stagger: 1">
        <div class="barber-pole-strip" aria-hidden="true"></div>

        <form method="POST" action="{{ route('calendar.appointments.store') }}">
            @csrf

            @php
                $clientType = old('client_type', 'existing');
            @endphp

            <fieldset class="form-fieldset">
                <legend class="form-legend">Tipo de cliente</legend>
                <div class="mode-toggle" role="radiogroup" aria-label="Tipo de cliente">
                    <label class="mode-toggle__option">
                        <input type="radio" name="client_type" value="existing"
                               @if($clientType === 'existing') checked @endif>
                        <span>Já tem conta</span>
                    </label>

                    <label class="mode-toggle__option">
                        <input type="radio" name="client_type" value="walk_in"
                               @if($clientType === 'walk_in') checked @endif>
                        <span>Sem conta (walk-in)</span>
                    </label>
                </div>
                @error('client_type')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </fieldset>

            {{-- Preencher só se o cliente tiver conta --}}
            <div class="form-group">
                <label for="customer_id">Cliente com conta</label>
                <select name="customer_id" id="customer_id">
                    <option value="">— Escolhe um cliente registado —</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @if((int) old('customer_id') === $client->id) selected @endif>
                            {{ $client->name }} ({{ $client->email }})
                        </option>
                    @endforeach
                </select>
                @error('customer_id')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Preencher só se o cliente for walk-in --}}
            <div class="form-grid-2">
                <div class="form-group">
                    <label for="walk_in_name">Nome do cliente (walk-in)</label>
                    <input type="text" name="walk_in_name" id="walk_in_name"
                           value="{{ old('walk_in_name') }}"
                           placeholder="Ex.: Manuel Ferreira">
                    @error('walk_in_name')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="walk_in_phone">Telefone (opcional)</label>
                    <input type="text" name="walk_in_phone" id="walk_in_phone"
                           value="{{ old('walk_in_phone') }}"
                           placeholder="Ex.: 912 345 678">
                    @error('walk_in_phone')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="service_id">Serviço a realizar</label>
                <select name="service_id" id="service_id" required>
                    <option value="">— Seleciona o serviço —</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}" @if((int) old('service_id') === $service->id) selected @endif>
                            {{ $service->name }} — {{ $service->duration_minutes }} min — {{ number_format((float) $service->price, 2, ',', '.') }} €
                        </option>
                    @endforeach
                </select>
                @error('service_id')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="date">Data</label>
                    <input type="date" name="date" id="date"
                           min="{{ now()->toDateString() }}"
                           value="{{ old('date', now()->toDateString()) }}" required>
                    @error('date')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="time">Hora de início</label>
                    <input type="time" name="time" id="time"
                           value="{{ old('time', '09:00') }}" required>
                    @error('time')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary">Guardar marcação</button>
                <a href="{{ route('calendar.mine') }}" class="btn">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection