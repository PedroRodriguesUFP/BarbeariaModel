@extends('layouts.barbearia')

@section('title', 'Avaliação #' . $review->id)

@section('content')
<div class="wrap">
    <header class="page-head">
        <div>
            <h1>Avaliação #{{ $review->id }}</h1>
            <p class="sub">{{ $review->reviewer?->name ?? '-' }}</p>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('reviews.edit', $review) }}">Editar</a>
            <a class="btn ghost" href="{{ route('reviews.index') }}">Voltar</a>
        </div>
    </header>

    <div class="page-body narrow">
        <div class="detail" data-tilt>
            <dl>
                <div class="row">
                    <dt>Classificação</dt>
                    <dd class="big lift-far">{{ $review->rating }}/5</dd>
                </div>
                <div class="row">
                    <dt>Avaliação sobre</dt>
                    <dd class="lift">
                        {{ $review->isAboutBarber() ? 'Barbeiro' : 'Serviço' }}:
                        {{ $review->reviewable?->name ?? '-' }}
                    </dd>
                </div>
                <div class="row">
                    <dt>Utilizador</dt>
                    <dd class="lift">{{ $review->reviewer?->name ?? '-' }}</dd>
                </div>
                <div class="row">
                    <dt>Comentário</dt>
                    <dd class="plain lift">{{ $review->comment ?: '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
