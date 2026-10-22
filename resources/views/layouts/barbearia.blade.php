<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title') · {{ config('app.name', 'Barbearia') }}</title>

    <meta
        name="description"
        content="@yield('description', 'Cortes, barba e cuidados. Escolha o serviço e marque a sua visita online.')"
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

    <div class="wrap">
        <header class="topbar">

            <a class="brand" href="{{ url('/') }}">
                {{ config('app.name', 'Barbearia') }}
            </a>

            <nav aria-label="Principal">

                <a
                    href="{{ route('services.index') }}"
                    @if (request()->routeIs('services.*')) aria-current="page" @endif
                >
                    Serviços
                </a>

                <a
                    href="{{ route('appointments.index') }}"
                    @if (request()->routeIs('appointments.*')) aria-current="page" @endif
                >
                    Marcações
                </a>

                <a
                    href="{{ route('payments.index') }}"
                    @if (request()->routeIs('payments.*')) aria-current="page" @endif
                >
                    Pagamentos
                </a>

                <a
                    href="{{ route('reviews.index') }}"
                    @if (request()->routeIs('reviews.*')) aria-current="page" @endif
                >
                    Avaliações
                </a>


                @auth

                    @if (
                        auth()->user()->role ===
                        \App\Enums\UserRole::Barber
                    )

                        <a
                            href="{{ route('calendar.mine') }}"
                            @if (request()->routeIs('calendar.*')) aria-current="page" @endif
                        >
                            Calendário
                        </a>

                    @elseif (
                        auth()->user()->role ===
                        \App\Enums\UserRole::Admin
                    )

                        <a
                            href="{{ route('barbers.index') }}"
                            @if (request()->routeIs('barbers.*')) aria-current="page" @endif
                        >
                            Barbeiros
                        </a>

                    @endif


                    <span>
                        Olá, {{ auth()->user()->name }}
                    </span>

                    <form
                        action="{{ route('logout') }}"
                        method="POST"
                        style="display:inline"
                    >
                        @csrf

                        <button type="submit">
                            Logout
                        </button>
                    </form>

                @else

                    <a
                        href="{{ route('login') }}"
                        @if (request()->routeIs('login')) aria-current="page" @endif
                    >
                        Login
                    </a>

                @endauth

            </nav>

            <div class="account">
                @auth
                    <span class="who">{{ auth()->user()->name }}</span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="link" type="submit">Sair</button>
                    </form>
                @else
                    <a class="btn small ghost" href="{{ route('login') }}">Entrar</a>
                @endauth
            </div>
        </header>
    </div>


    <main>
        @yield('content')
    </main>


    <footer class="site-footer">
        <div class="wrap">

            <span>
                &copy; {{ date('Y') }}
                {{ config('app.name', 'Barbearia') }}
            </span>

            <a href="{{ route('appointments.create') }}">
                Marcar horário
            </a>

        </div>
    </footer>

    @unless (request()->routeIs('appointments.create', 'login'))
        <a class="btn sticky-cta" href="{{ route('appointments.create') }}">Marcar horário</a>
    @endunless

    <script src="{{ asset('js/barbearia.js') }}" defer></script>
</body>
</html>