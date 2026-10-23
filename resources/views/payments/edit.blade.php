@extends('layouts.barbearia')

@section('title', 'Editar pagamento')

@section('content')
<div class="wrap">
    <header class="page-head">
        <h1>Editar pagamento</h1>
    </header>

    <div class="page-body narrow">
        @include('partials.messages')

        <div class="form-card">
            <form action="{{ route('payments.update', $payment) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="fields two">
                    <div class="field">
                        <label for="appointment_id">ID da marcação</label>
                        <input id="appointment_id" name="appointment_id" type="number" min="1" value="{{ old('appointment_id', $payment->appointment_id) }}" required>
                    </div>

                    <div class="field">
                        <label>Pagador</label>
                        <input type="text" value="{{ $payment->payer->name ?? ('#'.$payment->payer_id) }}" disabled>
                    </div>

                    <div class="field">
                        <label for="amount">Valor (€)</label>
                        <input id="amount" name="amount" type="number" step="0.01" min="0" value="{{ old('amount', $payment->amount) }}" required>
                    </div>

                    <div class="field">
                        <label for="method">Método</label>
                        <select id="method" name="method" required>
                            <option value="cash" @selected(old('method', $payment->method) === 'cash')>Dinheiro</option>
                            <option value="card" @selected(old('method', $payment->method) === 'card')>Cartão</option>
                            <option value="mbway" @selected(old('method', $payment->method) === 'mbway')>MB WAY</option>
                            <option value="transfer" @selected(old('method', $payment->method) === 'transfer')>Transferência</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="status">Estado</label>
                        <select id="status" name="status" required>
                            <option value="pending" @selected(old('status', $payment->status) === 'pending')>Pendente</option>
                            <option value="paid" @selected(old('status', $payment->status) === 'paid')>Pago</option>
                            <option value="failed" @selected(old('status', $payment->status) === 'failed')>Falhado</option>
                            <option value="refunded" @selected(old('status', $payment->status) === 'refunded')>Reembolsado</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="paid_at">Data de pagamento</label>
                        <input id="paid_at" name="paid_at" type="datetime-local" value="{{ old('paid_at', $payment->paid_at?->format('Y-m-d\TH:i')) }}">
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn" type="submit">Guardar alterações</button>
                    <a class="btn ghost" href="{{ route('payments.index') }}">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
