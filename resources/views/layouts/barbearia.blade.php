<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        @yield('title') · {{ config('app.name', 'Barbearia') }}
    </title>

    <meta
        name="description"
        content="@yield(
            'description',
            'Cortes, barba e cuidados. Escolha o serviço e marque a sua visita online.'
        )"
    >

    <link rel="preconnect" href="https://fonts.bunny.net">

    <link
        href="https://fonts.bunny.net/css?family=archivo:400,500,700,800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="{{ asset('css/barbearia.css') }}"
    >

    @stack('head')
</head>

<body>

    {{-- =====================================================
        TOPBAR
    ====================================================== --}}
    <div class="wrap">

        <header class="topbar">

            <a
                class="brand"
                href="{{ route('home') }}"
            >
                {{ config('app.name', 'Barbearia') }}
            </a>

            @php
                $u = auth()->user();
                $links = [];

                // Visitante
                $links[] = ['Serviços', route('services.index'), 'services.*'];

                if ($u?->isClient()) {
                    $links[] = ['Marcações', route('appointments.index'), 'appointments.*'];
                    $links[] = ['Pagamentos', route('payments.index'), 'payments.*'];
                    $links[] = ['Avaliações', route('reviews.index'), 'reviews.*'];
                } elseif ($u?->isBarber()) {
                    $links[] = ['Calendário', route('calendar.mine'), 'calendar.*'];
                    $links[] = ['Marcações', route('appointments.index'), 'appointments.*'];
                } elseif ($u?->isAdmin()) {
                    $links = [
                        ['Painel', route('admin.dashboard'), 'admin.dashboard'],
                        ['Barbeiros', route('admin.barbers.index'), 'admin.barbers.*'],
                        ['Contas', route('admin.users.index'), 'admin.users.*'],
                        ['Serviços', route('services.index'), 'services.*'],
                        ['Marcações', route('appointments.index'), 'appointments.*'],
                        ['Pagamentos', route('payments.index'), 'payments.*'],
                        ['Avaliações', route('reviews.index'), 'reviews.*'],
                    ];
                }
            @endphp

            <nav aria-label="Principal">
                @foreach ($links as [$label, $url, $pattern])
                    <a
                        href="{{ $url }}"
                        @if (request()->routeIs($pattern))
                            aria-current="page"
                        @endif
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </nav>


            {{-- CONTA --}}
            <div class="account">

                @auth

                    <span class="who">
                        {{ auth()->user()->name }}
                    </span>

                    <form
                        action="{{ route('logout') }}"
                        method="POST"
                    >
                        @csrf

                        <button
                            class="link"
                            type="submit"
                        >
                            Sair
                        </button>
                    </form>

                @else

                    <a
                        class="btn small ghost"
                        href="{{ route('login') }}"
                    >
                        Entrar
                    </a>

                @endauth

            </div>

        </header>

    </div>


    {{-- =====================================================
        SUBNAV DO BARBEIRO
    ====================================================== --}}
    @auth

        @if (auth()->user()->isBarber())

            <nav
                class="barber-subnav"
                aria-label="Área de trabalho do barbeiro"
            >

                <div class="wrap barber-subnav__inner">

                    <span class="barber-subnav__tag">
                        BANCADA
                    </span>

                    <div class="barber-subnav__links">

                        @if (Route::has('barber.dashboard'))
                            <a
                                href="{{ route('barber.dashboard') }}"
                                class="
                                    barber-subnav__link
                                    {{ request()->routeIs('barber.dashboard')
                                        ? 'is-active'
                                        : '' }}
                                "
                            >
                                Dashboard
                            </a>
                        @endif


                        <a
                            href="{{ route('calendar.mine') }}"
                            class="
                                barber-subnav__link
                                {{ request()->routeIs('calendar.*')
                                    ? 'is-active'
                                    : '' }}
                            "
                        >
                            Calendário
                        </a>


                        <a
                            href="{{ route('appointments.index') }}"
                            class="
                                barber-subnav__link
                                {{ request()->routeIs('appointments.*')
                                    ? 'is-active'
                                    : '' }}
                            "
                        >
                            Marcações
                        </a>


                        @if (Route::has('barber.availability'))
                            <a
                                href="{{ route('barber.availability') }}"
                                class="
                                    barber-subnav__link
                                    {{ request()->routeIs('barber.availability*')
                                        ? 'is-active'
                                        : '' }}
                                "
                            >
                                Disponibilidade
                            </a>
                        @endif


                        @if (Route::has('barber.services'))
                            <a
                                href="{{ route('barber.services') }}"
                                class="
                                    barber-subnav__link
                                    {{ request()->routeIs('barber.services')
                                        ? 'is-active'
                                        : '' }}
                                "
                            >
                                Serviços
                            </a>
                        @endif


                        @if (Route::has('barber.reviews'))
                            <a
                                href="{{ route('barber.reviews') }}"
                                class="
                                    barber-subnav__link
                                    {{ request()->routeIs('barber.reviews')
                                        ? 'is-active'
                                        : '' }}
                                "
                            >
                                Avaliações
                            </a>
                        @endif

                    </div>

                </div>

            </nav>

        @endif

    @endauth


    {{-- =====================================================
        CONTEÚDO
    ====================================================== --}}
    <main>
        @yield('content')
    </main>


    {{-- =====================================================
        FOOTER
    ====================================================== --}}
    <footer class="site-footer">

        <div class="wrap">

            <span>
                &copy;
                {{ date('Y') }}
                {{ config('app.name', 'Barbearia') }}
            </span>


            {{-- CLIENTE --}}
            @auth

                @if (auth()->user()->isClient() || auth()->user()->isAdmin())

                    <a href="{{ route('appointments.create') }}">
                        Marcar horário
                    </a>

                @elseif (
                    auth()->user()->isBarber() &&
                    Route::has('calendar.appointments.create')
                )

                    <a href="{{ route('calendar.appointments.create') }}">
                        Adicionar à agenda
                    </a>

                @endif

            {{-- VISITANTE --}}
            @else

                <a href="{{ route('login') }}">
                    Entrar para marcar
                </a>

            @endauth

        </div>

    </footer>


    {{-- =====================================================
        CTA FIXO MOBILE
    ====================================================== --}}
    @auth

        @if (
            (auth()->user()->isClient() || auth()->user()->isAdmin()) &&
            ! request()->routeIs('appointments.create')
        )

            <a
                class="btn sticky-cta"
                href="{{ route('appointments.create') }}"
            >
                Marcar horário
            </a>

        @elseif (
            auth()->user()->isBarber() &&
            Route::has('calendar.appointments.create') &&
            ! request()->routeIs('calendar.appointments.create')
        )

            <a
                class="btn sticky-cta"
                href="{{ route('calendar.appointments.create') }}"
            >
                Adicionar marcação
            </a>

        @endif

    @endauth


    <script
        src="{{ asset('js/barbearia.js') }}"
        defer
    ></script>

</body>
</html>