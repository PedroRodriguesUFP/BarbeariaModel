<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Barbearia') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-900 text-gray-100 antialiased font-sans min-h-screen flex flex-col justify-between">

    <header class="bg-neutral-950 border-b border-neutral-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="{{ url('/') }}" class="text-xl font-bold text-white tracking-wider uppercase">
                        Barbearia <span class="text-amber-500">Model</span>
                    </a>
                </div>

                <nav class="hidden md:flex space-x-6">
                    <a href="{{ route('services.index') }}" class="text-gray-300 hover:text-amber-500 text-sm font-medium transition-colors">Serviços</a>
                    <a href="{{ route('appointments.index') }}" class="text-gray-300 hover:text-amber-500 text-sm font-medium transition-colors">Marcações</a>
                    <a href="{{ route('payments.index') }}" class="text-gray-300 hover:text-amber-500 text-sm font-medium transition-colors">Pagamentos</a>
                    <a href="{{ route('reviews.index') }}" class="text-gray-300 hover:text-amber-500 text-sm font-medium transition-colors">Avaliações</a>
                </nav>

                <div class="flex items-center">
                    <a href="{{ route('appointments.create') }}" class="px-4 py-2 text-xs font-semibold rounded bg-amber-500 hover:bg-amber-400 text-neutral-950 transition-colors uppercase tracking-wider">
                        + Agendar
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-grow">
        @yield('content')
    </main>

    <footer class="bg-neutral-950 border-t border-neutral-800 py-6 text-center text-xs text-gray-500">
        <p>&copy; {{ date('Y') }} Barbearia. Todos os direitos reservados.</p>
    </footer>

</body>
</html>
