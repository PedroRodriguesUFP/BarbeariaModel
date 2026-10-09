<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do pagamento</title>
</head>
<body class="bg-gray-50 text-gray-900">
    <main class="mx-auto max-w-2xl px-4 py-10"><h1 class="mb-6 text-2xl font-bold">Detalhes do pagamento #{{ $payment->id }}</h1>
        <dl class="space-y-3 rounded border border-gray-200 bg-white p-6">
            <div><dt class="font-semibold">Agendamento</dt><dd>#{{ $payment->appointment_id }}</dd></div><div><dt class="font-semibold">Cliente</dt><dd>{{ $payment->client?->user?->name ?? '#' . $payment->client_id }}</dd></div><div><dt class="font-semibold">Barbeiro</dt><dd>{{ $payment->barber?->user?->name ?? ($payment->barber_id ? '#' . $payment->barber_id : '-') }}</dd></div><div><dt class="font-semibold">Valor</dt><dd>{{ number_format((float) $payment->amount, 2, ',', '.') }} €</dd></div><div><dt class="font-semibold">Método</dt><dd>{{ $payment->method }}</dd></div><div><dt class="font-semibold">Estado</dt><dd>{{ $payment->status }}</dd></div><div><dt class="font-semibold">Pago em</dt><dd>{{ $payment->paid_at?->format('d/m/Y H:i') ?? '-' }}</dd></div>
        </dl>
        <div class="mt-6 flex gap-3"><a class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700" href="{{ route('payments.edit', $payment) }}">Editar</a><a class="rounded border border-gray-300 px-4 py-2 hover:bg-gray-100" href="{{ route('payments.index') }}">Voltar</a></div>
    </main>
</body>
</html>