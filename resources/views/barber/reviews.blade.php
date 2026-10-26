@extends('layouts.barbearia')

@section('title', 'Avaliações — ' . $barber->name)

@section('content')
<div class="wrap page-body">
    <header class="page-head animate-enter">
        <div>
            <p class="eyebrow">FEEDBACK DE CLIENTES</p>
            <h1>Avaliações dos Meus Serviços</h1>
        </div>
        <div class="actions">
            <a href="{{ route('barber.dashboard') }}" class="btn">← Dashboard</a>
        </div>
    </header>

    <section class="barber-stats-grid" style="margin-bottom: 1.5rem;">
        <article class="card stat-placard animate-enter" data-tilt style="--stagger: 1">
            <span class="stat-placard__label lift">Média Global</span>
            <strong class="stat-placard__value lift-far">{{ number_format($averageRating, 1, ',', '.') }} / 5</strong>
            <span class="stat-placard__foot">Classificação média</span>
        </article>

        <article class="card stat-placard animate-enter" data-tilt style="--stagger: 2">
            <span class="stat-placard__label lift">Total de Avaliações</span>
            <strong class="stat-placard__value lift-far">{{ $totalReviews }}</strong>
            <span class="stat-placard__foot">Feedback registado</span>
        </article>
    </section>

    @if($reviews->isEmpty())
        <div class="card">
            <p class="muted">Ainda não existem avaliações registadas para os teus serviços.</p>
        </div>
    @else
        <div class="barber-cards-grid">
            @foreach($reviews as $index => $review)
                <article class="card animate-enter" data-tilt style="--stagger: {{ ($index % 6) + 1 }}">
                    <div class="row-actions" style="justify-content: space-between;">
                        <span class="badge">{{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', max(0, 5 - (int) $review->rating)) }} ({{ $review->rating }}/5)</span>
                        <small class="muted">{{ $review->created_at?->format('d/m/Y') }}</small>
                    </div>
                    <p style="margin: 0.85rem 0;">{{ $review->comment ?: 'Sem comentário escrito.' }}</p>
                    <div class="muted" style="font-size: 0.85rem;">
                        <strong>Cliente:</strong> {{ $review->reviewer?->name ?? 'Cliente' }}
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection