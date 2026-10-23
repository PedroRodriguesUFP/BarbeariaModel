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

            <nav aria-label="Principal">

                <a
                    href="{{ route('services.index') }}"
                    @if (request()->routeIs('services.*'))
                        aria-current="page"
                    @endif
                >
                    Serviços
                </a>

                @auth

                    <a
                        href="{{ route('appointments.index') }}"
                        @if (request()->routeIs('appointments.*'))
                            aria-current="page"
                        @endif
                    >
                        Marcações
                    </a>

                    <a
                        href="{{ route('payments.index') }}"
                        @if (request()->routeIs('payments.*'))
                            aria-current="page"
                        @endif
                    >
                        Pagamentos
                    </a>

                    <a
                        href="{{ route('reviews.index') }}"
                        @if (request()->routeIs('reviews.*'))
                            aria-current="page"
                        @endif
                    >
                        Avaliações
                    </a>


                    {{-- BARBEIRO --}}
                    @if (auth()->user()->isBarber())

                        <a
                            href="{{ route('calendar.mine') }}"
                            @if (request()->routeIs('calendar.*'))
                                aria-current="page"
                            @endif
                        >
                            Calendário
                        </a>

                    {{-- ADMIN --}}
                    @elseif (auth()->user()->isAdmin())

                        <a
                            href="{{ route('barbers.index') }}"
                            @if (request()->routeIs('barbers.*'))
                                aria-current="page"
                            @endif
                        >
                            Barbeiros
                        </a>

                    @endif

                @endauth

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

                @if (auth()->user()->isClient())

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
            auth()->user()->isClient() &&
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