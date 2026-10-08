<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo pagamento</title>
</head>
<body class="bg-gray-50 text-gray-900">
    <main class="mx-auto max-w-2xl px-4 py-10"><h1 class="mb-6 text-2xl font-bold">Novo pagamento</h1>
        @if ($errors->any())<ul class="mb-4 list-inside list-disc rounded border border-red-200 bg-red-50 p-3 text-red-800">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
        <form class="space-y-4 rounded border border-gray-200 bg-white p-6" action="{{ route('payments.store') }}" method="POST">
            @csrf
            <div><label class="mb-1 block font-medium" for="appointment_id">ID do agendamento</label><input class="w-full rounded border border-gray-300 px-3 py-2" id="appointment_id" name="appointment_id" type="number" min="1" value="{{ old('appointment_id') }}" required></div>
            <div><label class="mb-1 block font-medium" for="client_id">ID do cliente</label><input class="w-full rounded border border-gray-300 px-3 py-2" id="client_id" name="client_id" type="number" min="1" value="{{ old('client_id') }}" required></div>
            <div><label class="mb-1 block font-medium" for="barber_id">ID do barbeiro (opcional)</label><input class="w-full rounded border border-gray-300 px-3 py-2" id="barber_id" name="barber_id" type="number" min="1" value="{{ old('barber_id') }}"></div>
            <div><label class="mb-1 block font-medium" for="amount">Valor (€)</label><input class="w-full rounded border border-gray-300 px-3 py-2" id="amount" name="amount" type="number" step="0.01" min="0" value="{{ old('amount') }}" required></div>
            <div><label class="mb-1 block font-medium" for="method">Método</label><select class="w-full rounded border border-gray-300 px-3 py-2" id="method" name="method" required><option value="">Selecionar método</option><option value="cash" @selected(old('method') === 'cash')>Dinheiro</option><option value="card" @selected(old('method') === 'card')>Cartão</option><option value="mbway" @selected(old('method') === 'mbway')>MB WAY</option><option value="transfer" @selected(old('method') === 'transfer')>Transferência</option></select></div>
            <div><label class="mb-1 block font-medium" for="status">Estado</label><select class="w-full rounded border border-gray-300 px-3 py-2" id="status" name="status" required><option value="pending" @selected(old('status', 'pending') === 'pending')>Pendente</option><option value="paid" @selected(old('status') === 'paid')>Pago</option><option value="failed" @selected(old('status') === 'failed')>Falhado</option><option value="refunded" @selected(old('status') === 'refunded')>Reembolsado</option></select></div>
            <div><label class="mb-1 block font-medium" for="paid_at">Data de pagamento</label><input class="w-full rounded border border-gray-300 px-3 py-2" id="paid_at" name="paid_at" type="datetime-local" value="{{ old('paid_at') }}"></div>
            <div class="flex gap-3"><button class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700" type="submit">Criar pagamento</button><a class="rounded border border-gray-300 px-4 py-2 hover:bg-gray-100" href="{{ route('payments.index') }}">Voltar</a></div>
        </form>
    </main>
</body>
</html>