<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('app.name', 'Barbearia') }}</title>
    <meta name="description" content="@yield('description', 'Cortes, barba e cuidados. Escolha o serviço e marque a sua visita online.')">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=archivo:400,500,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/barbearia.css') }}">
</head>
<body>
    <div class="wrap">
        <header class="topbar">
            <a class="brand" href="{{ url('/') }}">{{ config('app.name', 'Barbearia') }}</a>
                       <nav aria-label="Principal">
                <a href="{{ route('services.index') }}" @if (request()->routeIs('services.*')) aria-current="page" @endif>Serviços</a>
                <a href="{{ route('appointments.index') }}" @if (request()->routeIs('appointments.*')) aria-current="page" @endif>Marcações</a>
                <a href="{{ route('payments.index') }}" @if (request()->routeIs('payments.*')) aria-current="page" @endif>Pagamentos</a>
                <a href="{{ route('reviews.index') }}" @if (request()->routeIs('reviews.*')) aria-current="page" @endif>Avaliações</a>

                @auth
                    <span>Olá, {{ auth()->user()->name }}</span>

                    <form action="{{ route('logout') }}" method="POST" style="display:inline">
                        @csrf
                        <button type="submit">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" @if (request()->routeIs('login')) aria-current="page" @endif>Login</a>
                @endauth
            </nav>
        </header>
    </div>

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="wrap">
            <span>&copy; {{ date('Y') }} {{ config('app.name', 'Barbearia') }}</span>
            <a href="{{ route('appointments.create') }}">Marcar horário</a>
        </div>
    </footer>

    <script src="{{ asset('js/barbearia.js') }}" defer></script>
</body>
</html>
