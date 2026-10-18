@extends('layouts.barbearia')

@section('title', 'Serviços')

@php
    $categorias = [
        'hair' => 'Cabelo',
        'beard' => 'Barba',
        'hair and beard' => 'Cabelo e barba',
        'fade' => 'Fade',
        'coloring' => 'Coloração',
    ];
@endphp

@section('content')
<div class="wrap">
    <header class="page-head">
        <div>
            <h1>Serviços</h1>
            <p class="sub">Preço e duração de cada serviço.</p>
        </div>
        @if (auth()->user()?->isAdmin())
            <a class="btn" href="{{ route('services.create') }}">Novo serviço</a>
        @endif
    </header>

    <div class="page-body">
        @include('partials.messages')

        @if ($services->isEmpty())
            <p class="empty">
                Ainda não existem serviços.
                @if (auth()->user()?->isAdmin())
                    <a href="{{ route('services.create') }}">Adicionar o primeiro serviço</a>
                @endif
            </p>
        @else
            <div class="cards">
                @foreach ($services as $service)
                    @php($categoria = $service->category->value)
                    <article class="card" data-tilt>
                        <span class="tag lift">{{ $categorias[$categoria] ?? $categoria }}</span>
                        <h2 class="lift">{{ $service->name }}</h2>
                        <span class="price lift-far">{{ number_format($service->price, 2, ',', ' ') }} €</span>
                        <span class="meta lift">{{ $service->duration_minutes }} min</span>

                        <div class="row-actions lift">
                            <a class="link" href="{{ route('services.show', $service) }}">Ver</a>
                            @if (auth()->user()?->isAdmin())
                                <a class="link" href="{{ route('services.edit', $service) }}">Editar</a>
                                <form
                                    action="{{ route('services.destroy', $service) }}"
                                    method="POST"
                                    onsubmit="return confirm('Eliminar este serviço?')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button class="link danger" type="submit">Eliminar</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
