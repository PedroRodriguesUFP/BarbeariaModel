<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar pagamento</title>
</head>

<body class="bg-gray-50 text-gray-900">

<main class="mx-auto max-w-2xl px-4 py-10">

    <h1 class="mb-6 text-2xl font-bold">
        Editar pagamento
    </h1>

    @if ($errors->any())
        <ul class="mb-4 list-inside list-disc rounded border border-red-200 bg-red-50 p-3 text-red-800">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form
        class="space-y-4 rounded border border-gray-200 bg-white p-6"
        action="{{ route('payments.update', $payment) }}"
        method="POST"
    >
        @csrf
        @method('PUT')

        <div>
            <label for="appointment_id">
                ID da marcação
            </label>

            <input
                id="appointment_id"
                name="appointment_id"
                type="number"
                min="1"
                value="{{ old(
                    'appointment_id',
                    $payment->appointment_id
                ) }}"
                required
            >
        </div>

        <div>
            <label for="payer_id">
                ID do utilizador que paga
            </label>

            <input
                id="payer_id"
                name="payer_id"
                type="number"
                min="1"
                value="{{ old(
                    'payer_id',
                    $payment->payer_id
                ) }}"
                required
            >
        </div>

        <div>
            <label for="amount">
                Valor (€)
            </label>

            <input
                id="amount"
                name="amount"
                type="number"
                step="0.01"
                min="0"
                value="{{ old(
                    'amount',
                    $payment->amount
                ) }}"
                required
            >
        </div>

        <div>
            <label for="method">
                Método
            </label>

            <select
                id="method"
                name="method"
                required
            >
                <option
                    value="cash"
                    @selected(old('method', $payment->method) === 'cash')
                >
                    Dinheiro
                </option>

                <option
                    value="card"
                    @selected(old('method', $payment->method) === 'card')
                >
                    Cartão
                </option>

                <option
                    value="mbway"
                    @selected(old('method', $payment->method) === 'mbway')
                >
                    MB WAY
                </option>

                <option
                    value="transfer"
                    @selected(old('method', $payment->method) === 'transfer')
                >
                    Transferência
                </option>
            </select>
        </div>

        <div>
            <label for="status">
                Estado
            </label>

            <select
                id="status"
                name="status"
                required
            >
                <option
                    value="pending"
                    @selected(old('status', $payment->status) === 'pending')
                >
                    Pendente
                </option>

                <option
                    value="paid"
                    @selected(old('status', $payment->status) === 'paid')
                >
                    Pago
                </option>

                <option
                    value="failed"
                    @selected(old('status', $payment->status) === 'failed')
                >
                    Falhado
                </option>

                <option
                    value="refunded"
                    @selected(old('status', $payment->status) === 'refunded')
                >
                    Reembolsado
                </option>
            </select>
        </div>

        <div>
            <label for="paid_at">
                Data de pagamento
            </label>

            <input
                id="paid_at"
                name="paid_at"
                type="datetime-local"
                value="{{ old(
                    'paid_at',
                    $payment->paid_at?->format('Y-m-d\TH:i')
                ) }}"
            >
        </div>

        <div class="flex gap-3">

            <button type="submit">
                Guardar alterações
            </button>

            <a href="{{ route('payments.index') }}">
                Voltar
            </a>

        </div>

    </form>

</main>

</body>
</html>