<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da avaliação</title>
</head>
<body class="bg-gray-50 text-gray-900">
    <main class="mx-auto max-w-2xl px-4 py-10"><h1 class="mb-6 text-2xl font-bold">Detalhes da avaliação #{{ $review->id }}</h1>
        <dl class="space-y-3 rounded border border-gray-200 bg-white p-6">
            <div><dt class="font-semibold">Cliente</dt><dd>{{ $review->client?->user?->name ?? '#' . $review->client_id }}</dd></div><div><dt class="font-semibold">Avaliação sobre</dt><dd>{{ class_basename($review->reviewable_type) }} #{{ $review->reviewable_id }}</dd></div><div><dt class="font-semibold">Classificação</dt><dd>{{ $review->rating }}/5</dd></div><div><dt class="font-semibold">Comentário</dt><dd>{{ $review->comment ?? '-' }}</dd></div>
        </dl>
        <div class="mt-6 flex gap-3"><a class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700" href="{{ route('reviews.edit', $review) }}">Editar</a><a class="rounded border border-gray-300 px-4 py-2 hover:bg-gray-100" href="{{ route('reviews.index') }}">Voltar</a></div>
    </main>
</body>
</html>