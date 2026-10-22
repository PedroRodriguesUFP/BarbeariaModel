@extends('layouts.barbearia')

@section('title', 'Nova marcação na agenda')

@section('content')
<div class="wrap">
    <header class="page-head">
        <h1>Nova marcação na agenda</h1>
    </header>

    <div class="page-body narrow">
        @include('partials.messages')

        <div class="form-card">
            <form action="{{ route('calendar.appointments.store') }}" method="POST">
                @csrf

                <div class="field">
                    <span class="hint">Cliente</span>

                    <label class="radio-option">
                        <input type="radio" name="client_type" value="existing" {{ old('client_type', 'existing') === 'existing' ? 'checked' : '' }}>
                        Já tem conta
                    </label>

                    <label for="customer_id" class="hint">Cliente existente</label>
                    <select id="customer_id" name="customer_id">
                        <option value="">Escolher cliente…</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" {{ (string) old('customer_id') === (string) $client->id ? 'selected' : '' }}>
                                {{ $client->name }} — {{ $client->email }}
                            </option>
                        @endforeach
                    </select>

                    <label class="radio-option">
                        <input type="radio" name="client_type" value="walk_in" {{ old('client_type') === 'walk_in' ? 'checked' : '' }}>
                        Sem conta (walk-in)
                    </label>
                </div>

                <div class="fields two">
                    <div class="field">
                        <label for="walk_in_name">Nome (walk-in)</label>
                        <input id="walk_in_name" name="walk_in_name" type="text" value="{{ old('walk_in_name') }}">
                    </div>

                    <div class="field">
                        <label for="walk_in_phone">Telefone (opcional)</label>
                        <input id="walk_in_phone" name="walk_in_phone" type="text" value="{{ old('walk_in_phone') }}">
                    </div>
                </div>

                <p class="hint">
                    Walk-in sem conta: depois de atendido, convida-o a criar conta —
                    a marcação fica registada à mesma, só não está ligada a um utilizador.
                </p>

                <div class="fields two">
                    <div class="field full">
                        <label for="service_id">ID do serviço</label>
                        <input id="service_id" name="service_id" type="number" min="1" value="{{ old('service_id') }}" required>
                    </div>

                    <div class="field">
                        <label for="date">Data</label>
                        <input id="date" name="date" type="date" value="{{ old('date') }}" required>
                    </div>

                    <div class="field">
                        <label for="time">Hora</label>
                        <input id="time" name="time" type="time" value="{{ old('time') }}" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn" type="submit">Adicionar à agenda</button>
                    <a class="btn ghost" href="{{ route('calendar.mine') }}">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection