<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Barbearia') }}</title>
</head>
<body>
    <header>
        <h1>{{ config('app.name', 'Barbearia') }}</h1>
        <p>Cortes e cuidados para o seu estilo.</p>
    </header>

    <main>
        <h2>Bem-vindo à nossa barbearia</h2>
        <p>Escolha um serviço e marque a sua próxima visita.</p>
        <p>
            <a href="{{ route('services.index') }}">Ver serviços</a>
            |
            <a href="{{ route('appointments.create') }}">Marcar horário</a>
        </p>
    </main>

    <footer>
        <p>&copy; {{ date('Y') }} {{ config('app.name', 'Barbearia') }}</p>
    </footer>
</body>
</html>
