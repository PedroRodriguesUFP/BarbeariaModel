<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barbeiros</title>
</head>
<body class="bg-gray-50 text-gray-900">
    <main class="mx-auto max-w-6xl px-4 py-10">
        <header class="mb-6 flex items-center justify-between gap-4"><h1 class="text-2xl font-bold">Barbeiros</h1><a class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700" href="{{ route('barbers.create') }}">Novo barbeiro</a></header>
        @if (session('success'))<p class="mb-4 rounded border border-green-200 bg-green-50 p-3 text-green-800">{{ session('success') }}</p>@endif
        <div class="overflow-x-auto rounded border border-gray-200 bg-white"><table class="w-full border-collapse text-left text-sm">
            <thead class="bg-gray-100 text-gray-700"><tr><th class="p-3">Nome</th><th class="p-3">Email</th><th class="p-3">Telefone</th><th class="p-3">Ações</th></tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($barbers as $barber)
                    <tr><td class="p-3">{{ $barber->user?->name ?? '-' }}</td><td class="p-3">{{ $barber->user?->email ?? '-' }}</td><td class="p-3">{{ $barber->phone ?? '-' }}</td><td class="p-3"><div class="flex gap-3"><a class="text-blue-700 hover:underline" href="{{ route('barbers.show', $barber) }}">Ver</a><a class="text-blue-700 hover:underline" href="{{ route('barbers.edit', $barber) }}">Editar</a><form action="{{ route('barbers.destroy', $barber) }}" method="POST" onsubmit="return confirm('Eliminar este barbeiro?')">@csrf @method('DELETE')<button class="text-red-700 hover:underline" type="submit">Eliminar</button></form></div></td></tr>
                @empty
                    <tr><td class="p-4 text-center text-gray-500" colspan="4">Ainda não existem barbeiros.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </main>
</body>
</html>