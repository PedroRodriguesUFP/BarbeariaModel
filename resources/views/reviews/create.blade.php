<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova avaliação</title>
</head>
<body class="bg-gray-50 text-gray-900">
    <main class="mx-auto max-w-2xl px-4 py-10"><h1 class="mb-6 text-2xl font-bold">Nova avaliação</h1>
        @if ($errors->any())<ul class="mb-4 list-inside list-disc rounded border border-red-200 bg-red-50 p-3 text-red-800">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
        <form class="space-y-4 rounded border border-gray-200 bg-white p-6" action="{{ route('reviews.store') }}" method="POST">
            @csrf
            <div><label class="mb-1 block font-medium" for="client_id">ID do cliente</label><input class="w-full rounded border border-gray-300 px-3 py-2" id="client_id" name="client_id" type="number" min="1" value="{{ old('client_id') }}" required></div>
            <div><label class="mb-1 block font-medium" for="reviewable_type">Avaliação sobre</label><select class="w-full rounded border border-gray-300 px-3 py-2" id="reviewable_type" name="reviewable_type" required><option value="">Selecionar tipo</option><option value="App\Models\Barber" @selected(old('reviewable_type') === 'App\Models\Barber')>Barbeiro</option><option value="App\Models\Service" @selected(old('reviewable_type') === 'App\Models\Service')>Serviço</option></select></div>
            <div><label class="mb-1 block font-medium" for="reviewable_id">ID do barbeiro ou serviço</label><input class="w-full rounded border border-gray-300 px-3 py-2" id="reviewable_id" name="reviewable_id" type="number" min="1" value="{{ old('reviewable_id') }}" required></div>
            <div><label class="mb-1 block font-medium" for="rating">Classificação</label><select class="w-full rounded border border-gray-300 px-3 py-2" id="rating" name="rating" required><option value="">Selecionar classificação</option>@foreach (range(1, 5) as $rating)<option value="{{ $rating }}" @selected((string) old('rating') === (string) $rating)>{{ $rating }} / 5</option>@endforeach</select></div>
            <div><label class="mb-1 block font-medium" for="comment">Comentário</label><textarea class="w-full rounded border border-gray-300 px-3 py-2" id="comment" name="comment" rows="4">{{ old('comment') }}</textarea></div>
            <div class="flex gap-3"><button class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700" type="submit">Criar avaliação</button><a class="rounded border border-gray-300 px-4 py-2 hover:bg-gray-100" href="{{ route('reviews.index') }}">Voltar</a></div>
        </form>
    </main>
</body>
</html>