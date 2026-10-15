<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Barbearia') }}</title>
    <meta name="description" content="Cortes, barba e cuidados. Escolha o serviço e marque a sua visita online.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=archivo:400,500,700,800&display=swap" rel="stylesheet">

    <style>
        :root {
            --paper: #e8edf0;
            --ink: #12243a;
            --steel: #566678;
            --line: #c5cfd8;
            --red: #c23b2e;
            --red-dark: #a42e23;
            --sky: #8fb3d1;
            --white: #ffffff;
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --paper: #0e1b2c;
                --ink: #e8edf0;
                --steel: #9bacbd;
                --line: #263a52;
                --red: #e0594b;
                --red-dark: #c94a3d;
                --sky: #8fb3d1;
                --white: #0e1b2c;
            }
        }

        *, *::before, *::after { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            background: var(--paper);
            color: var(--ink);
            font-family: 'Archivo', system-ui, -apple-system, 'Segoe UI', sans-serif;
            font-size: 1.0625rem;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; }

        :focus-visible {
            outline: 3px solid var(--sky);
            outline-offset: 3px;
        }

        .wrap {
            width: 100%;
            max-width: 68rem;
            margin: 0 auto;
            padding: 0 1rem;
        }

        @media (min-width: 48rem) {
            .wrap { padding: 0 2rem; }
        }

        /* Cabeçalho */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.25rem 0;
        }

        .brand {
            font-weight: 800;
            font-size: 1.25rem;
            letter-spacing: -0.01em;
            text-decoration: none;
        }

        .topbar nav {
            display: flex;
            gap: 1.5rem;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .topbar nav a { text-decoration: none; }
        .topbar nav a:hover { text-decoration: underline; text-underline-offset: 4px; }

        @media (max-width: 36rem) {
            .topbar nav a:not(.keep) { display: none; }
        }

        /* Hero */
        .hero {
            display: grid;
            gap: 2.5rem;
            padding: 2.5rem 0 4rem;
            align-items: center;
        }

        @media (min-width: 52rem) {
            .hero {
                grid-template-columns: 1fr 9rem;
                gap: 4rem;
                padding: 4rem 0 6rem;
            }
        }

        h1 {
            margin: 0 0 1.25rem;
            font-size: clamp(2.5rem, 8vw, 5.25rem);
            line-height: 0.98;
            letter-spacing: -0.035em;
            font-weight: 800;
            max-width: 12ch;
        }

        .lead {
            margin: 0 0 2rem;
            max-width: 34rem;
            color: var(--steel);
            font-size: 1.1875rem;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
        }

        .btn {
            display: inline-block;
            padding: 0.9rem 1.6rem;
            border-radius: 0.25rem;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            border: 2px solid var(--red);
            background: var(--red);
            color: #fff;
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }

        .btn:hover { background: var(--red-dark); border-color: var(--red-dark); }

        .btn.ghost {
            background: transparent;
            color: var(--ink);
            border-color: var(--ink);
        }

        .btn.ghost:hover { background: var(--ink); color: var(--paper); border-color: var(--ink); }

        /* Poste de barbeiro: o único momento de movimento da página */
        .pole-wrap {
            display: flex;
            justify-content: center;
        }

        .pole {
            position: relative;
            width: 6.5rem;
            height: 20rem;
            border-radius: 3.25rem;
            border: 4px solid var(--ink);
            background-color: var(--white);
            background-image: repeating-linear-gradient(
                -45deg,
                var(--red) 0 1.4rem,
                var(--white) 1.4rem 2.8rem,
                var(--sky) 2.8rem 4.2rem,
                var(--white) 4.2rem 5.6rem
            );
            background-size: 100% 7.9rem;
            animation: spin 2.4s linear infinite;
            box-shadow: inset 0.9rem 0 1.2rem -0.6rem rgba(255, 255, 255, 0.45),
                        inset -0.9rem 0 1.2rem -0.6rem rgba(18, 36, 58, 0.3);
        }

        @media (max-width: 51.99rem) {
            .pole-wrap { justify-content: flex-start; }
            .pole { width: 100%; height: 3.5rem; border-radius: 1.75rem; max-width: 22rem;
                    background-size: 7.9rem 100%; animation-name: spin-x; }
        }

        @keyframes spin   { to { background-position: 0 7.9rem; } }
        @keyframes spin-x { to { background-position: 7.9rem 0; } }

        @media (prefers-reduced-motion: reduce) {
            .pole { animation: none; }
        }

        /* Serviços */
        .section { padding: 3.5rem 0; border-top: 2px solid var(--ink); }

        .section h2 {
            margin: 0 0 0.5rem;
            font-size: clamp(1.75rem, 4vw, 2.5rem);
            letter-spacing: -0.02em;
            line-height: 1.1;
        }

        .section .sub { margin: 0 0 2rem; color: var(--steel); max-width: 34rem; }

        .menu { list-style: none; margin: 0; padding: 0; }

        .menu li {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 0.25rem 1.5rem;
            padding: 1.25rem 0;
            border-bottom: 1px solid var(--line);
        }

        .menu .name { font-weight: 700; font-size: 1.25rem; }
        .menu .meta { color: var(--steel); font-size: 0.95rem; grid-column: 1; }
        .menu .price {
            grid-row: 1 / span 2;
            grid-column: 2;
            align-self: center;
            font-weight: 800;
            font-size: 1.5rem;
            font-variant-numeric: tabular-nums;
        }

        .empty {
            padding: 1.5rem;
            border: 2px dashed var(--line);
            border-radius: 0.25rem;
            color: var(--steel);
        }

        .empty a { color: var(--red); font-weight: 700; }

        .more { margin-top: 1.75rem; }

        /* Passos para marcar */
        .steps {
            display: grid;
            gap: 2rem;
            margin: 0;
            padding: 0;
            list-style: none;
            counter-reset: passo;
        }

        @media (min-width: 48rem) {
            .steps { grid-template-columns: repeat(3, 1fr); gap: 3rem; }
        }

        .steps li { counter-increment: passo; }

        .steps li::before {
            content: counter(passo);
            display: inline-grid;
            place-items: center;
            width: 2.5rem;
            height: 2.5rem;
            margin-bottom: 0.75rem;
            border-radius: 50%;
            background: var(--ink);
            color: var(--paper);
            font-weight: 800;
        }

        .steps strong { display: block; font-size: 1.125rem; margin-bottom: 0.25rem; }
        .steps span { color: var(--steel); }

        /* Rodapé */
        footer {
            padding: 2rem 0 3rem;
            border-top: 2px solid var(--ink);
            color: var(--steel);
            font-size: 0.95rem;
        }

        footer .wrap {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 1rem;
        }

        footer a { text-underline-offset: 4px; }
    </style>
</head>
<body>
    <div class="wrap">
        <header class="topbar">
            <a class="brand" href="{{ url('/') }}">{{ config('app.name', 'Barbearia') }}</a>
            <nav aria-label="Principal">
                <a href="{{ route('services.index') }}">Serviços</a>
                <a href="{{ route('reviews.index') }}">Avaliações</a>
                <a class="keep" href="{{ route('appointments.create') }}">Marcar</a>
            </nav>
        </header>

        <main>
            <section class="hero" aria-labelledby="titulo">
                <div>
                    <h1 id="titulo">Corte certo, à hora marcada.</h1>
                    <p class="lead">
                        Cabelo, barba e fade com barbeiros que conhecem o ofício.
                        Escolha o serviço e reserve o seu lugar em menos de um minuto.
                    </p>
                    <div class="actions">
                        <a class="btn" href="{{ route('appointments.create') }}">Marcar horário</a>
                        <a class="btn ghost" href="#servicos">Ver serviços</a>
                    </div>
                </div>

                <div class="pole-wrap" aria-hidden="true">
                    <div class="pole"></div>
                </div>
            </section>

            <section class="section" id="servicos" aria-labelledby="servicos-titulo">
                <h2 id="servicos-titulo">Serviços e preços</h2>
                <p class="sub">Preços finais, com a duração de cada serviço.</p>

                @if ($services->isEmpty())
                    <p class="empty">
                        Ainda não há serviços disponíveis.
                        <a href="{{ route('services.create') }}">Adicionar o primeiro serviço</a>
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
        </main>
    </div>

    <footer>
        <div class="wrap">
            <span>&copy; {{ date('Y') }} {{ config('app.name', 'Barbearia') }}</span>
            <a href="{{ route('appointments.create') }}">Marcar horário</a>
        </div>
    </footer>
</body>
</html>