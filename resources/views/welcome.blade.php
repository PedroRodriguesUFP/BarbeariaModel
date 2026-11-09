@extends('layouts.barbearia')

@section('title', 'Corte certo, à hora marcada')

@section('content')
<div class="wrap">
    <section class="hero" aria-labelledby="titulo">
        <div>
            <h1>Corte certo, à hora marcada</h1>
            <p class="lead">
                Cabelo, barba e fade com barbeiros que conhecem o ofício.
                Escolha o serviço e reserve o seu lugar em menos de um minuto.
            </p>
            <div class="actions">
                @guest
                    <a class="btn" href="{{ route('login') }}">Entrar para marcar</a>
                @else
                    @if (auth()->user()->isClient())
                        <a class="btn" href="{{ route('appointments.create') }}">Marcar horário</a>
                    @elseif (auth()->user()->isBarber())
                        <a class="btn" href="{{ route('calendar.mine') }}">Abrir calendário</a>
                    @else
                        <a class="btn" href="{{ route('admin.dashboard') }}">Abrir painel</a>
                    @endif
                @endguest
                <a class="btn ghost" href="#servicos">Ver serviços</a>
            </div>
        </div>

        <div class="stage" aria-hidden="true" data-stage>
            <div class="stage__floor"></div>

            <div class="stage__scene">
                <div class="pole-stage">
                    <div class="pole"></div>
                </div>

                @php
                    $orbit = [
                        ['maquina', 'Máquina e degradê'],
                        ['degrade', 'Textura e nuca'],
                        ['barba', 'Barba e contornos'],
                    ];
                @endphp

                <ul class="ring" style="--n: {{ count($orbit) }}">
                    @foreach ($orbit as $i => [$img, $label])
                        <li style="--i: {{ $i }}">
                            <figure class="shot">
                                <img
                                    src="{{ asset('img/barbearia/'.$img.'.jpg') }}"
                                    alt=""
                                    width="560"
                                    height="700"
                                    loading="eager"
                                    decoding="async"
                                >
                                <figcaption>{{ $label }}</figcaption>
                            </figure>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <section class="section" id="servicos" aria-labelledby="servicos-titulo">
        <h2 id="servicos-titulo">Serviços e preços</h2>
        <p class="sub">Preços finais, com a duração de cada serviço.</p>

        @if ($services->isEmpty())
            <p class="empty">
                Ainda não há serviços disponíveis.
                @if (auth()->user()?->isAdmin())
                    <a href="{{ route('services.create') }}">Adicionar o primeiro serviço</a>
                @endif
            </p>
        @else
            <ul class="menu">
                @foreach ($services as $service)
                    <li>
                        <span class="name">{{ $service->name }}</span>
                        <span class="price">{{ number_format($service->price, 2, ',', ' ') }} €</span>
                        <span class="meta">
                            {{ $service->duration_minutes }} min
                            @if ($service->description)
                                &middot; {{ \Illuminate\Support\Str::limit($service->description, 90) }}
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>

            <p class="more">
                <a href="{{ route('services.index') }}">Ver todos os serviços</a>
            </p>
        @endif
    </section>

    <section class="section" aria-labelledby="como-titulo">
        <h2 id="como-titulo">Como marcar</h2>
        <p class="sub">Três passos, sem telefonemas.</p>

        <ol class="steps">
            <li>
                <strong>Escolha o serviço</strong>
                <span>Veja a duração e o preço antes de decidir.</span>
            </li>
            <li>
                <strong>Escolha o horário</strong>
                <span>Só aparecem as horas em que há barbeiro livre.</span>
            </li>
            <li>
                <strong>Confirme e pague</strong>
                <span>Pague online e receba a confirmação da marcação.</span>
            </li>
        </ol>
    </section>
</div>
@endsection
