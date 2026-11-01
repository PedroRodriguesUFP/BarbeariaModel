@extends('layouts.barbearia')

@section('title', 'Os Meus Serviços — ' . $barber->name)

@section('content')
<div class="wrap page-body">
    <header class="page-head animate-enter">
        <div>
            <p class="eyebrow">CATÁLOGO HABILITADO</p>
            <h1>Os Meus Serviços</h1>
            <p class="muted">Serviços que estás habilitado a realizar na barbearia.</p>
        </div>
        <div class="actions">
            <a href="{{ route('barber.dashboard') }}" class="btn">← Dashboard</a>
        </div>
    </header>

    @if($services->isEmpty())
        <div class="card">
            <p class="muted">Ainda não tens servicos associados ao teu perfil.</p>
        </div>
    @else
        <div class="barber-cards-grid">
            @foreach($services as $index => $service)
                <article class="card animate-enter" data-tilt style="--stagger: {{ ($index % 6) + 1 }}">
                    <div class="row-actions" style="justify-content: space-between; margin-bottom: 0.5rem;">
                        <span class="badge">{{ $service->category->value }}</span>
                        <strong>{{ number_format((float) $service->price, 2, ',', '.') }} €</strong>
                    </div>
                    <h2 class="lift">{{ $service->name }}</h2>
                    <p class="muted" style="margin: 0.35rem 0 0.75rem;">Duração: <strong>{{ $service->duration_minutes }} min</strong></p>
                    @if($service->description)
                        <p>{{ $service->description }}</p>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection