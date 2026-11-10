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
                    <div class="field full">
                        <label>Avaliação sobre</label>
                        <p>{{ $review->isAboutBarber() ? 'Barbeiro' : 'Serviço' }}: {{ $review->reviewable?->name ?? '-' }}</p>
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
