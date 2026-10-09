<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo cliente</title>
</head>
<body class="bg-gray-50 text-gray-900">
    <main class="mx-auto max-w-2xl px-4 py-10"><h1 class="mb-6 text-2xl font-bold">Novo cliente</h1>
        @if ($errors->any())<ul class="mb-4 list-inside list-disc rounded border border-red-200 bg-red-50 p-3 text-red-800">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
        <form class="space-y-4 rounded border border-gray-200 bg-white p-6" action="{{ route('clients.store') }}" method="POST">
            @csrf
            <div><label class="mb-1 block font-medium" for="user_id">ID do utilizador</label><input class="w-full rounded border border-gray-300 px-3 py-2" id="user_id" name="user_id" type="number" min="1" value="{{ old('user_id') }}" required></div>
            <div><label class="mb-1 block font-medium" for="phone">Telefone</label><input class="w-full rounded border border-gray-300 px-3 py-2" id="phone" name="phone" type="tel" value="{{ old('phone') }}"></div>
            <div><label class="mb-1 block font-medium" for="tax_id">NIF</label><input class="w-full rounded border border-gray-300 px-3 py-2" id="tax_id" name="tax_id" type="text" value="{{ old('tax_id') }}"></div>
            <div class="flex gap-3"><button class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700" type="submit">Criar cliente</button><a class="rounded border border-gray-300 px-4 py-2 hover:bg-gray-100" href="{{ route('clients.index') }}">Voltar</a></div>
        </form>
    </main>
</body>
</html>