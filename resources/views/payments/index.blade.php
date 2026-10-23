@extends('layouts.barbearia')

@section('title', 'Pagamentos')

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
            <h1>Pagamentos</h1>
            <p class="sub">Valores recebidos por cada marcação.</p>
        </div>
        @can('create', \App\Models\Payment::class)
            <a class="btn" href="{{ route('payments.create') }}">Novo pagamento</a>
        @endcan
    </header>

    <div class="page-body">
        @include('partials.messages')

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Marcação</th>
                        <th>Utilizador</th>
                        <th>Barbeiro</th>
                        <th>Valor</th>
                        <th>Método</th>
                        <th>Estado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="num">#{{ $payment->appointment_id }}</td>
                            <td>{{ $payment->payer?->name ?? '-' }}</td>
                            <td>{{ $payment->appointment?->barber?->name ?? '-' }}</td>
                            <td class="num">{{ $payment->getFormattedAmount() }}</td>
                            <td>{{ $metodos[$payment->method] ?? $payment->method }}</td>
                            <td>
                                <span class="badge badge-{{ \Illuminate\Support\Str::slug($payment->status) }}">
                                    {{ $estados[$payment->status] ?? $payment->status }}
                                </span>
                            </td>
                            <td>
                                <div class="row-actions">
                                    @can('view', $payment)
                                        <a class="link" href="{{ route('payments.show', $payment) }}">Ver</a>
                                    @endcan
                                    @can('update', $payment)
                                        <a class="link" href="{{ route('payments.edit', $payment) }}">Editar</a>
                                    @endcan
                                    @can('delete', $payment)
                                        <form
                                            action="{{ route('payments.destroy', $payment) }}"
                                            method="POST"
                                            onsubmit="return confirm('Eliminar este pagamento?')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button class="link danger" type="submit">Eliminar</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="table-empty" colspan="7">
                                Ainda não existem pagamentos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
