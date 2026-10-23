@extends('layouts.barbearia')

@section('title', 'Marcação #' . $appointment->id)

@section('content')

@php
    $estado = $appointment->status?->value;
    $isBarber = auth()->check()
        && (int) auth()->id() === (int) $appointment->barber_id;
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
            AÇÕES DO BARBEIRO
        ====================================================== --}}
        @if ($isBarber)

            <div
                class="card barber-status-actions animate-enter"
                style="margin-top: 2rem;"
            >

                <span class="tag">
                    AÇÕES DO BARBEIRO
                </span>

                <h2>
                    Atualizar marcação
                </h2>

                <p>
                    Altera o estado desta marcação conforme o atendimento.
                </p>


                <div class="row-actions">

                    {{-- CONFIRMAR --}}
                    @if (
                        $appointment->status ===
                        \App\Enums\Status::Pending &&
                        Route::has('appointments.confirm')
                    )

                        <form
                            method="POST"
                            action="{{ route(
                                'appointments.confirm',
                                $appointment
                            ) }}"
                        >

                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="btn small"
                            >
                                Confirmar
                            </button>

                        </form>

                    @endif


                    {{-- CONCLUIR --}}
                    @if (
                        $appointment->status ===
                        \App\Enums\Status::Confirmed &&
                        Route::has('appointments.complete')
                    )

                        <form
                            method="POST"
                            action="{{ route(
                                'appointments.complete',
                                $appointment
                            ) }}"
                        >

                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="btn small"
                            >
                                Concluir serviço
                            </button>

                        </form>

                    @endif


                    {{-- CANCELAR --}}
                    @if (
                        in_array(
                            $appointment->status,
                            [
                                \App\Enums\Status::Pending,
                                \App\Enums\Status::Confirmed,
                            ],
                            true
                        ) &&
                        Route::has('appointments.cancel')
                    )

                        <form
                            method="POST"
                            action="{{ route(
                                'appointments.cancel',
                                $appointment
                            ) }}"
                            onsubmit="return confirm(
                                'Cancelar esta marcação?'
                            )"
                        >

                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="btn small ghost"
                            >
                                Cancelar
                            </button>

                        </form>

                    @endif

                </div>

            </div>

        @endif

    </div>

</div>

@endsection