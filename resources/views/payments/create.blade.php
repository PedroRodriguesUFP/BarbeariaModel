@extends('layouts.barbearia')

@section('title', 'Novo pagamento')

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
@endphp

<div class="wrap">
    <header class="page-head">
        <h1>{{ $isAdmin ? 'Novo pagamento' : 'Pagar uma marcação' }}</h1>
    </header>

    <div class="page-body narrow">
        @include('partials.messages')

        @if ($isAdmin)

            {{-- ============================================================
                ADMIN: regista um pagamento à mão
            ============================================================= --}}
            <div class="form-card">
                <form action="{{ route('payments.store') }}" method="POST">
                    @csrf

                    <div class="fields two">
                        <div class="field">
                            <label for="appointment_id">ID da marcação</label>
                            <input id="appointment_id" name="appointment_id" type="number" min="1" value="{{ old('appointment_id') }}" required>
                        </div>

                        <div class="field">
                            <label for="amount">Valor (€)</label>
                            <input id="amount" name="amount" type="number" step="0.01" min="0" value="{{ old('amount') }}" required>
                        </div>

                        <div class="field">
                            <label for="method">Método</label>
                            <select id="method" name="method" required>
                                <option value="">Selecionar método</option>
                                <option value="cash" @selected(old('method') === 'cash')>Dinheiro</option>
                                <option value="card" @selected(old('method') === 'card')>Cartão</option>
                                <option value="mbway" @selected(old('method') === 'mbway')>MB WAY</option>
                                <option value="transfer" @selected(old('method') === 'transfer')>Transferência</option>
                            </select>
                        </div>

                        <div class="field">
                            <label for="status">Estado</label>
                            <select id="status" name="status" required>
                                <option value="pending" @selected(old('status', 'pending') === 'pending')>Pendente</option>
                                <option value="paid" @selected(old('status') === 'paid')>Pago</option>
                                <option value="failed" @selected(old('status') === 'failed')>Falhado</option>
                                <option value="refunded" @selected(old('status') === 'refunded')>Reembolsado</option>
                            </select>
                        </div>

                        <div class="field">
                            <label for="paid_at">Data de pagamento</label>
                            <input id="paid_at" name="paid_at" type="datetime-local" value="{{ old('paid_at') }}">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn" type="submit">Criar pagamento</button>
                        <a class="btn ghost" href="{{ route('payments.index') }}">Voltar</a>
                    </div>
                </form>
            </div>

        @else

            {{-- ============================================================
                CLIENTE: escolhe uma marcação sua e paga com cartão (Stripe).
                O valor é sempre o preço do serviço.
            ============================================================= --}}
            <div class="form-card">
                @forelse ($appointments as $appointment)
                    @php
                        $preco = number_format((float) ($appointment->service?->price ?? 0), 2, ',', '.');
                    @endphp

                    <div class="row" style="display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:0.75rem 0;">
                        <div>
                            <strong>Marcação #{{ $appointment->id }}</strong>
                            · {{ $appointment->service?->name ?? 'Serviço' }}
                            <br>
                            <span class="sub">
                                {{ $appointment->date?->format('d/m/Y') }}
                                @if ($appointment->time)
                                    {{ substr($appointment->time, 0, 5) }}
                                @endif
                                · {{ $appointment->barber?->name ?? '-' }}
                                · {{ $preco }} €
                            </span>
                        </div>

                        <form action="{{ route('payments.checkout', $appointment) }}" method="POST">
                            @csrf
                            <button class="btn small" type="submit">Pagar com cartão</button>
                        </form>
                    </div>
                @empty
                    <p>Não tens marcações por pagar.</p>
                @endforelse

                <div class="form-actions">
                    <a class="btn ghost" href="{{ route('payments.index') }}">Voltar</a>
                </div>
            </div>

        @endif
    </div>
</div>
@endsection
