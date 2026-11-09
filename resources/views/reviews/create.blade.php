@extends('layouts.barbearia')

@section('title', 'Nova avaliação')

@section('content')
<div class="wrap">
    <header class="page-head">
        <h1>Nova avaliação</h1>
    </header>

    <div class="page-body narrow">
        @include('partials.messages')

        <div class="form-card">
            <form action="{{ route('reviews.store') }}" method="POST">
                @csrf

                <div class="fields two">
                    <!-- Tipo de Avaliação (US-2: Valores limpos, sem FQCN) -->
                    <div class="field">
                        <label for="reviewable_type">Avaliação sobre</label>
                        <select id="reviewable_type" 
                                name="reviewable_type" 
                                class="{{ $errors->has('reviewable_type') ? 'is-invalid' : '' }}" 
                                required>
                            <option value="">Selecionar tipo</option>
                            <option value="barber" @selected(old('reviewable_type') === 'barber')>Barbeiro</option>
                            <option value="service" @selected(old('reviewable_type') === 'service')>Serviço</option>
                        </select>
                        @include('partials.field-error', ['field' => 'reviewable_type'])
                    </div>

                    <!-- ID do Alvo -->
                    <div class="field">
                        <label for="reviewable_id">ID do barbeiro ou serviço</label>
                        <input id="reviewable_id" 
                               name="reviewable_id" 
                               type="number" 
                               min="1" 
                               value="{{ old('reviewable_id') }}" 
                               class="{{ $errors->has('reviewable_id') ? 'is-invalid' : '' }}" 
                               required>
                        @include('partials.field-error', ['field' => 'reviewable_id'])
                    </div>

                    <!-- Classificação -->
                    <div class="field full">
                        <label for="rating">Classificação</label>
                        <select id="rating" 
                                name="rating" 
                                class="{{ $errors->has('rating') ? 'is-invalid' : '' }}" 
                                required>
                            <option value="">Selecionar classificação</option>
                            @foreach (range(1, 5) as $score)
                                <option value="{{ $score }}" @selected((string) old('rating') === (string) $score)>
                                    {{ $score }} / 5
                                </option>
                            @endforeach
                        </select>
                        @include('partials.field-error', ['field' => 'rating'])
                    </div>

                    <!-- Comentário -->
                    <div class="field full">
                        <label for="comment">Comentário</label>
                        <textarea id="comment" 
                                  name="comment" 
                                  rows="4" 
                                  class="{{ $errors->has('comment') ? 'is-invalid' : '' }}">{{ old('comment') }}</textarea>
                        @include('partials.field-error', ['field' => 'comment'])
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn" type="submit">Criar avaliação</button>
                    <a class="btn ghost" href="{{ route('reviews.index') }}">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection