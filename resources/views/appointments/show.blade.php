@extends('layouts.barbearia')

@section('title', 'Marcação #' . $appointment->id)

@section('content')

@php
    $estado = $appointment->status?->value;
@endphp

<div class="wrap">

    <header class="page-head">

        <div>
            <h1>Marcação #{{ $appointment->id }}</h1>

            <p class="sub">
                {{ $appointment->service?->name ?? 'Serviço não encontrado' }}
            </p>
        </div>

        <div class="actions">

            @can('update', $appointment)
                <a
                    class="btn"
                    href="{{ route('appointments.edit', $appointment) }}"
                >
                    Editar
                </a>
            @endcan

            <a
                class="btn ghost"
                href="{{ route('appointments.index') }}"
            >
                Voltar
            </a>

        </div>

    </header>


    <div class="page-body narrow">

        @include('partials.messages')


        {{-- =====================================================
            DETALHE DA MARCAÇÃO
        ====================================================== --}}
        <div
            class="detail"
            data-tilt
        >

            <dl>

                <div class="row">

                    <dt>Data</dt>

                    <dd class="big lift-far">
                        {{ $appointment->date?->format('d/m/Y') ?? '-' }}
                    </dd>

                </div>


                <div class="row">

                    <dt>Hora</dt>

                    <dd class="lift">
                        {{ $appointment->time
                            ? substr($appointment->time, 0, 5)
                            : '-' }}
                    </dd>

                </div>


                <div class="row">

                    <dt>Cliente</dt>

                    <dd class="lift">
                        {{ $appointment->customerDisplayName() }}
                    </dd>

                </div>


                <div class="row">

                    <dt>Barbeiro</dt>

                    <dd class="lift">
                        {{ $appointment->barber?->name
                            ?? 'Barbeiro não encontrado' }}
                    </dd>

                </div>


                <div class="row">

                    <dt>Serviço</dt>

                    <dd class="lift">
                        {{ $appointment->service?->name
                            ?? 'Serviço não encontrado' }}
                    </dd>

                </div>


                <div class="row">

                    <dt>Estado</dt>

                    <dd class="lift">

                        @if ($estado)

                            <span
                                class="badge badge-{{ \Illuminate\Support\Str::slug($estado) }}"
                            >
                                {{ ucfirst($estado) }}
                            </span>

                        @else

                            -

                        @endif

                    </dd>

                </div>

            </dl>

        </div>


        {{-- =====================================================
            PAGAMENTO (só o cliente da marcação)
        ====================================================== --}}
        @if ($appointment->canBePaidBy(auth()->user()))

            @php
                $preco = number_format((float) ($appointment->service?->price ?? 0), 2, ',', '.');
            @endphp

            <div class="card" style="margin-top: 2rem;">
                <span class="tag">PAGAMENTO</span>

                <h2>Pagar esta marcação</h2>

                <p>
                    {{ $appointment->service?->name ?? 'Serviço' }} · {{ $preco }} €
                </p>

                <form
                    method="POST"
                    action="{{ route('payments.checkout', $appointment) }}"
                >
                    @csrf

                    <button type="submit" class="btn">
                        Pagar com cartão
                    </button>
                </form>
            </div>

        @elseif (
            (int) auth()->id() === (int) $appointment->customer_id
            && $appointment->isPaid()
        )

            <p class="flash" style="margin-top: 2rem;">
                Esta marcação já está paga.
            </p>

        @endif


        {{-- =====================================================
            AÇÕES SOBRE O ESTADO (cada botão segue a AppointmentPolicy)
        ====================================================== --}}
        @canany(['confirm', 'complete', 'cancel'], $appointment)

            <div class="card barber-status-actions animate-enter" style="margin-top: 2rem;">

                <span class="tag">AÇÕES</span>

                <h2>Atualizar marcação</h2>

                <div class="row-actions">

                    @can('confirm', $appointment)
                        <form method="POST" action="{{ route('appointments.confirm', $appointment) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn small">Confirmar</button>
                        </form>
                    @endcan

                    @can('complete', $appointment)
                        <form method="POST" action="{{ route('appointments.complete', $appointment) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn small">Concluir serviço</button>
                        </form>
                    @endcan

                    @can('cancel', $appointment)
                        <form
                            method="POST"
                            action="{{ route('appointments.cancel', $appointment) }}"
                            onsubmit="return confirm('Cancelar esta marcação?')"
                        >
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn small ghost">Cancelar</button>
                        </form>
                    @endcan

                </div>

            </div>

        @endcanany

    </div>

</div>

@endsection