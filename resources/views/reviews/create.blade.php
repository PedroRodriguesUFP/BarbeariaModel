@extends('layouts.barbearia')

@section('title', 'Nova avaliação')

@section('content')
<div class="wrap">
    <header class="page-head">
        <h1>Nova avaliação</h1>
    </header>

    <div class="page-body narrow">
        @include('partials.messages')

        @if ($appointments->isEmpty())
            <p class="empty">
                Não tens marcações concluídas por avaliar.
                <a href="{{ route('reviews.index') }}">Voltar às avaliações</a>
            </p>
        @else
            <div class="form-card">
                <form action="{{ route('reviews.store') }}" method="POST">
                    @csrf

                    <div class="fields two">
                        <div class="field full">
                            <label for="appointment_id">Marcação a avaliar</label>
                            <select id="appointment_id" name="appointment_id" required>
                                <option value="">Selecionar marcação</option>
                                @foreach ($appointments as $appointment)
                                    <option value="{{ $appointment->id }}"
                                        @selected((string) old('appointment_id', $selectedId) === (string) $appointment->id)>
                                        {{ $appointment->date->format('d/m/Y') }}
                                        &middot; {{ $appointment->service?->name }}
                                        &middot; {{ $appointment->barber?->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field full">
                            <label for="barber_rating">Classificação do barbeiro</label>
                            <select id="barber_rating" name="barber_rating" required>
                                <option value="">Selecionar classificação</option>
                                @foreach (range(1, 5) as $rating)
                                    <option value="{{ $rating }}" @selected((string) old('barber_rating') === (string) $rating)>{{ $rating }} / 5</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field full">
                            <label for="barber_comment">Comentário sobre o barbeiro</label>
                            <textarea id="barber_comment" name="barber_comment" rows="3">{{ old('barber_comment') }}</textarea>
                        </div>

                        <div class="field full">
                            <label for="service_rating">Classificação do serviço (corte)</label>
                            <select id="service_rating" name="service_rating" required>
                                <option value="">Selecionar classificação</option>
                                @foreach (range(1, 5) as $rating)
                                    <option value="{{ $rating }}" @selected((string) old('service_rating') === (string) $rating)>{{ $rating }} / 5</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field full">
                            <label for="service_comment">Comentário sobre o serviço</label>
                            <textarea id="service_comment" name="service_comment" rows="3">{{ old('service_comment') }}</textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn" type="submit">Criar avaliações</button>
                        <a class="btn ghost" href="{{ route('reviews.index') }}">Voltar</a>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
