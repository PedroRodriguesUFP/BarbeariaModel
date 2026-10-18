@extends('layouts.barbearia')

@section('title', $service->name)

@php
    $categorias = [
        'hair' => 'Cabelo',
        'beard' => 'Barba',
        'hair and beard' => 'Cabelo e barba',
        'fade' => 'Fade',
        'coloring' => 'Coloração',
    ];
    $categoria = $service->category->value;
@endphp

@section('content')
<div class="wrap">
    <header class="page-head">
        <div>
            <h1>{{ $service->name }}</h1>
            <p class="sub">{{ $categorias[$categoria] ?? $categoria }}</p>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('services.edit', $service) }}">Editar</a>
            <a class="btn ghost" href="{{ route('services.index') }}">Voltar</a>
        </div>
    </header>

    <div class="page-body narrow">
        <div class="detail" data-tilt>
            <dl>
                <div class="row">
                    <dt>Preço</dt>
                    <dd class="big lift-far">{{ number_format($service->price, 2, ',', ' ') }} €</dd>
                </div>
                <div class="row">
                    <dt>Duração</dt>
                    <dd class="lift">{{ $service->duration_minutes }} min</dd>
                </div>
                <div class="row">
                    <dt>Categoria</dt>
                    <dd class="lift">{{ $categorias[$categoria] ?? $categoria }}</dd>
                </div>
                <div class="row">
                    <dt>Descrição</dt>
                    <dd class="plain lift">{{ $service->description ?: '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
