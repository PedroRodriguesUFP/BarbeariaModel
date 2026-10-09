<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamentos</title>
</head>
<body class="bg-gray-50 text-gray-900">
    <main class="mx-auto max-w-6xl px-4 py-10">
        <header class="mb-6 flex items-center justify-between gap-4">
            <h1 class="text-2xl font-bold">Agendamentos</h1>
            <a class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700" href="{{ route('appointments.create') }}">Novo agendamento</a>
        </header>

        @if (session('success'))
            <p class="mb-4 rounded border border-green-200 bg-green-50 p-3 text-green-800">{{ session('success') }}</p>
        @endif

        <div class="overflow-x-auto rounded border border-gray-200 bg-white">
            <table class="w-full border-collapse text-left text-sm">
                <thead class="bg-gray-100 text-gray-700">
                    <tr><th class="p-3">Data e hora</th><th class="p-3">Cliente</th><th class="p-3">Barbeiro</th><th class="p-3">Serviço</th><th class="p-3">Estado</th><th class="p-3">Ações</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($appointments as $appointment)
                        <tr>
                            <td class="p-3">{{ $appointment->date ?? $appointment->scheduled_at ?? '-' }} {{ $appointment->time ?? '' }}</td>
                            <td class="p-3">#{{ $appointment->client_id }}</td>
                            <td class="p-3">#{{ $appointment->barber_id }}</td>
                            <td class="p-3">#{{ $appointment->service_id }}</td>
                            <td class="p-3">{{ $appointment->status?->value ?? $appointment->status ?? '-' }}</td>
                            <td class="p-3">
                                <div class="flex gap-3">
                                    <a class="text-blue-700 hover:underline" href="{{ route('appointments.show', $appointment->id) }}">Ver</a>
                                    <a class="text-blue-700 hover:underline" href="{{ route('appointments.edit', $appointment->id) }}">Editar</a>
                                    <form action="{{ route('appointments.destroy', $appointment->id) }}" method="POST" onsubmit="return confirm('Eliminar este agendamento?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-700 hover:underline" type="submit">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="p-4 text-center text-gray-500" colspan="6">Ainda não existem agendamentos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>