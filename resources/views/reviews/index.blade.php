@extends('layouts.barbearia')

@section('title', 'Avaliações')

@section('content')
<div class="wrap">
    <header class="page-head">
        <div>
            <h1>Avaliações</h1>
            <p class="sub">O que os clientes dizem dos barbeiros e dos serviços.</p>
        </div>
        <a class="btn" href="{{ route('reviews.create') }}">Nova avaliação</a>
    </header>

    <div class="page-body">
        @include('partials.messages')

        @if ($reviews->isEmpty())
            <p class="empty">
                Ainda não existem avaliações.
                <a href="{{ route('reviews.create') }}">Escrever a primeira avaliação</a>
            </p>
        @else
            <div class="cards">
                @foreach ($reviews as $review)
                    <article class="card" data-tilt>
                        <span class="stars lift-far" role="img" aria-label="{{ $review->rating }} de 5">
                            {{ str_repeat('★', (int) $review->rating) }}<span class="off">{{ str_repeat('★', max(0, 5 - (int) $review->rating)) }}</span>
                        </span>

                        <p class="quote lift">{{ $review->comment ?: 'Sem comentário.' }}</p>

                        <span class="meta lift">
                            {{ $review->reviewer?->name ?? '-' }}
                            &middot;
                            {{ $review->isAboutBarber() ? 'Barbeiro' : 'Serviço' }}:
                            {{ $review->reviewable?->name ?? '-' }}
                        </span>

                        <div class="row-actions lift">
                            <a class="link" href="{{ route('reviews.show', $review) }}">Ver</a>
                            <a class="link" href="{{ route('reviews.edit', $review) }}">Editar</a>
                            <form
                                action="{{ route('reviews.destroy', $review) }}"
                                method="POST"
                                onsubmit="return confirm('Eliminar esta avaliação?')"
                            >
                                @csrf
                                @method('DELETE')
                                <button class="link danger" type="submit">Eliminar</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
