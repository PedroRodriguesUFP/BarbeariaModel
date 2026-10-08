<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar barbeiro</title>
</head>
<body class="bg-gray-50 text-gray-900">
    <main class="mx-auto max-w-2xl px-4 py-10"><h1 class="mb-6 text-2xl font-bold">Editar barbeiro</h1>
        @if ($errors->any())<ul class="mb-4 list-inside list-disc rounded border border-red-200 bg-red-50 p-3 text-red-800">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
        <form class="space-y-4 rounded border border-gray-200 bg-white p-6" action="{{ route('barbers.update', $barber) }}" method="POST">
            @csrf
            @method('PUT')
            <div><label class="mb-1 block font-medium" for="user_id">ID do utilizador</label><input class="w-full rounded border border-gray-300 px-3 py-2" id="user_id" name="user_id" type="number" min="1" value="{{ old('user_id', $barber->user_id) }}" required></div>
            <div><label class="mb-1 block font-medium" for="phone">Telefone</label><input class="w-full rounded border border-gray-300 px-3 py-2" id="phone" name="phone" type="tel" value="{{ old('phone', $barber->phone) }}"></div>
            <div><label class="mb-1 block font-medium" for="bio">Biografia</label><textarea class="w-full rounded border border-gray-300 px-3 py-2" id="bio" name="bio" rows="4">{{ old('bio', $barber->bio) }}</textarea></div>
            <div class="flex gap-3"><button class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700" type="submit">Guardar alterações</button><a class="rounded border border-gray-300 px-4 py-2 hover:bg-gray-100" href="{{ route('barbers.index') }}">Voltar</a></div>
        </form>
    </main>
</body>
</html>