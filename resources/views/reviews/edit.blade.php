@extends('layouts.barbearia')

@section('title', 'Editar avaliação')

@section('content')
<div class="wrap">
    <header class="page-head">
        <h1>Editar avaliação</h1>
    </header>

    <div class="page-body narrow">
        @include('partials.messages')

        <div class="form-card">
            <form action="{{ route('reviews.update', $review) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="fields two">
                    <div class="field">
                        <label for="reviewable_type">Avaliação sobre</label>
                        <select id="reviewable_type" name="reviewable_type" required>
                            <option value="App\Models\User" @selected(old('reviewable_type', $review->reviewable_type) === 'App\Models\User')>Barbeiro</option>
                            <option value="App\Models\Service" @selected(old('reviewable_type', $review->reviewable_type) === 'App\Models\Service')>Serviço</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="reviewable_id">ID do barbeiro ou serviço</label>
                        <input id="reviewable_id" name="reviewable_id" type="number" min="1" value="{{ old('reviewable_id', $review->reviewable_id) }}" required>
                    </div>

                    <div class="field full">
                        <label for="rating">Classificação</label>
                        <select id="rating" name="rating" required>
                            @foreach (range(1, 5) as $rating)
                                <option value="{{ $rating }}" @selected((string) old('rating', $review->rating) === (string) $rating)>
                                    {{ $rating }} / 5
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field full">
                        <label for="comment">Comentário</label>
                        <textarea id="comment" name="comment" rows="4">{{ old('comment', $review->comment) }}</textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn" type="submit">Guardar alterações</button>
                    <a class="btn ghost" href="{{ route('reviews.index') }}">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
