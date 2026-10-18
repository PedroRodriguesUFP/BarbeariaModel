@extends('layouts.barbearia')

@section('title', 'Nova marcação')

@section('content')
<div class="wrap">
    <header class="page-head">
        <h1>Nova marcação</h1>
    </header>

    <div class="page-body narrow">
        @include('partials.messages')

        <div class="form-card">
            <form action="{{ route('appointments.store') }}" method="POST">
                @csrf

                <div class="fields two">
                    <div class="field">
                        <label for="customer_id">ID do utilizador que marca</label>
                        <input id="customer_id" name="customer_id" type="number" min="1" value="{{ old('customer_id') }}" required>
                    </div>

                    <div class="field">
                        <label for="barber_id">ID do barbeiro</label>
                        <input id="barber_id" name="barber_id" type="number" min="1" value="{{ old('barber_id') }}" required>
                    </div>

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
                    <button class="btn" type="submit">Criar marcação</button>
                    <a class="btn ghost" href="{{ route('appointments.index') }}">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
