<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do barbeiro</title>
</head>
<body class="bg-gray-50 text-gray-900">
    <main class="mx-auto max-w-2xl px-4 py-10"><h1 class="mb-6 text-2xl font-bold">Detalhes do barbeiro</h1>
        <dl class="space-y-3 rounded border border-gray-200 bg-white p-6">
            <div><dt class="font-semibold">Nome</dt><dd>{{ $barber->user?->name ?? '-' }}</dd></div><div><dt class="font-semibold">Email</dt><dd>{{ $barber->user?->email ?? '-' }}</dd></div><div><dt class="font-semibold">Telefone</dt><dd>{{ $barber->phone ?? '-' }}</dd></div><div><dt class="font-semibold">Biografia</dt><dd>{{ $barber->bio ?? '-' }}</dd></div>
        </dl>
        <div class="mt-6 flex gap-3"><a class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700" href="{{ route('barbers.edit', $barber) }}">Editar</a><a class="rounded border border-gray-300 px-4 py-2 hover:bg-gray-100" href="{{ route('barbers.index') }}">Voltar</a></div>
    </main>
</body>
</html>