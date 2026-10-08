<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do agendamento</title>
</head>
<body class="bg-gray-50 text-gray-900">
    <main class="mx-auto max-w-2xl px-4 py-10">
        <h1 class="mb-6 text-2xl font-bold">Detalhes do agendamento #{{ $appointment->id }}</h1>
        <dl class="space-y-3 rounded border border-gray-200 bg-white p-6">
            <div><dt class="font-semibold">Data</dt><dd>{{ $appointment->date ?? $appointment->scheduled_at ?? '-' }}</dd></div>
            <div><dt class="font-semibold">Hora</dt><dd>{{ $appointment->time ?? '-' }}</dd></div>
            <div><dt class="font-semibold">Cliente</dt><dd>#{{ $appointment->client_id }}</dd></div>
            <div><dt class="font-semibold">Barbeiro</dt><dd>#{{ $appointment->barber_id }}</dd></div>
            <div><dt class="font-semibold">Serviço</dt><dd>#{{ $appointment->service_id }}</dd></div>
            <div><dt class="font-semibold">Estado</dt><dd>{{ $appointment->status?->value ?? $appointment->status ?? '-' }}</dd></div>
        </dl>
        <div class="mt-6 flex gap-3"><a class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700" href="{{ route('appointments.edit', $appointment->id) }}">Editar</a><a class="rounded border border-gray-300 px-4 py-2 hover:bg-gray-100" href="{{ route('appointments.index') }}">Voltar</a></div>
    </main>
</body>
</html>