<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar agendamento</title>
</head>
<body class="bg-gray-50 text-gray-900">
    <main class="mx-auto max-w-2xl px-4 py-10">
        <h1 class="mb-6 text-2xl font-bold">Editar agendamento</h1>
        @if ($errors->any())
            <ul class="mb-4 list-inside list-disc rounded border border-red-200 bg-red-50 p-3 text-red-800">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        @endif
        <form class="space-y-4 rounded border border-gray-200 bg-white p-6" action="{{ route('appointments.update', $appointment->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div><label class="mb-1 block font-medium" for="client_id">ID do cliente</label><input class="w-full rounded border border-gray-300 px-3 py-2" id="client_id" name="client_id" type="number" min="1" value="{{ old('client_id', $appointment->client_id) }}" required></div>
            <div><label class="mb-1 block font-medium" for="barber_id">ID do barbeiro</label><input class="w-full rounded border border-gray-300 px-3 py-2" id="barber_id" name="barber_id" type="number" min="1" value="{{ old('barber_id', $appointment->barber_id) }}" required></div>
            <div><label class="mb-1 block font-medium" for="service_id">ID do serviço</label><input class="w-full rounded border border-gray-300 px-3 py-2" id="service_id" name="service_id" type="number" min="1" value="{{ old('service_id', $appointment->service_id) }}" required></div>
            <div><label class="mb-1 block font-medium" for="date">Data</label><input class="w-full rounded border border-gray-300 px-3 py-2" id="date" name="date" type="date" value="{{ old('date', $appointment->date ?? '') }}" required></div>
            <div><label class="mb-1 block font-medium" for="time">Hora</label><input class="w-full rounded border border-gray-300 px-3 py-2" id="time" name="time" type="time" value="{{ old('time', $appointment->time ?? '') }}" required></div>
            <div class="flex gap-3"><button class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700" type="submit">Guardar alterações</button><a class="rounded border border-gray-300 px-4 py-2 hover:bg-gray-100" href="{{ route('appointments.index') }}">Voltar</a></div>
        </form>
    </main>
</body>
</html>