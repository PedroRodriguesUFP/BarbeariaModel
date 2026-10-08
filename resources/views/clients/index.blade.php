<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes</title>
</head>
<body class="bg-gray-50 text-gray-900">
    <main class="mx-auto max-w-6xl px-4 py-10">
        <header class="mb-6 flex items-center justify-between gap-4"><h1 class="text-2xl font-bold">Clientes</h1><a class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700" href="{{ route('clients.create') }}">Novo cliente</a></header>
        @if (session('success'))<p class="mb-4 rounded border border-green-200 bg-green-50 p-3 text-green-800">{{ session('success') }}</p>@endif
        <div class="overflow-x-auto rounded border border-gray-200 bg-white"><table class="w-full border-collapse text-left text-sm">
            <thead class="bg-gray-100 text-gray-700"><tr><th class="p-3">Nome</th><th class="p-3">Email</th><th class="p-3">Telefone</th><th class="p-3">NIF</th><th class="p-3">Ações</th></tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($clients as $client)
                    <tr><td class="p-3">{{ $client->user?->name ?? '-' }}</td><td class="p-3">{{ $client->user?->email ?? '-' }}</td><td class="p-3">{{ $client->phone ?? '-' }}</td><td class="p-3">{{ $client->tax_id ?? '-' }}</td><td class="p-3"><div class="flex gap-3"><a class="text-blue-700 hover:underline" href="{{ route('clients.show', $client) }}">Ver</a><a class="text-blue-700 hover:underline" href="{{ route('clients.edit', $client) }}">Editar</a><form action="{{ route('clients.destroy', $client) }}" method="POST" onsubmit="return confirm('Eliminar este cliente?')">@csrf @method('DELETE')<button class="text-red-700 hover:underline" type="submit">Eliminar</button></form></div></td></tr>
                @empty
                    <tr><td class="p-4 text-center text-gray-500" colspan="5">Ainda não existem clientes.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </main>
</body>
</html>