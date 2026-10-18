@extends('layouts.barbearia')

@section('title', 'Pagamento #' . $payment->id)

@php
    $metodos = [
        'cash' => 'Dinheiro',
        'card' => 'Cartão',
        'mbway' => 'MB WAY',
        'transfer' => 'Transferência',
    ];
    $estados = [
        'pending' => 'Pendente',
        'paid' => 'Pago',
        'failed' => 'Falhado',
        'refunded' => 'Reembolsado',
    ];
@endphp

@section('content')
<div class="wrap">
    <header class="page-head">
        <div>
            <h1>Pagamento #{{ $payment->id }}</h1>
            <p class="sub">Marcação #{{ $payment->appointment_id }}</p>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('payments.edit', $payment) }}">Editar</a>
            <a class="btn ghost" href="{{ route('payments.index') }}">Voltar</a>
        </div>
    </header>

    <div class="page-body narrow">
        <div class="detail" data-tilt>
            <dl>
                <div class="row">
                    <dt>Valor</dt>
                    <dd class="big lift-far">{{ $payment->getFormattedAmount() }}</dd>
                </div>
                <div class="row">
                    <dt>Estado</dt>
                    <dd class="lift">
                        <span class="badge badge-{{ \Illuminate\Support\Str::slug($payment->status) }}">
                            {{ $estados[$payment->status] ?? $payment->status }}
                        </span>
                    </dd>
                </div>
                <div class="row">
                    <dt>Método</dt>
                    <dd class="lift">{{ $metodos[$payment->method] ?? $payment->method }}</dd>
                </div>
                <div class="row">
                    <dt>Marcação</dt>
                    <dd class="lift">#{{ $payment->appointment_id }}</dd>
                </div>
                <div class="row">
                    <dt>Utilizador</dt>
                    <dd class="lift">{{ $payment->payer?->name ?? '-' }}</dd>
                </div>
                <div class="row">
                    <dt>Barbeiro</dt>
                    <dd class="lift">{{ $payment->appointment?->barber?->name ?? '-' }}</dd>
                </div>
                <div class="row">
                    <dt>Serviço</dt>
                    <dd class="lift">{{ $payment->appointment?->service?->name ?? '-' }}</dd>
                </div>
                <div class="row">
                    <dt>Pago em</dt>
                    <dd class="lift">{{ $payment->paid_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
