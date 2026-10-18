@extends('layouts.barbearia')

@section('title', 'Marcação #' . $appointment->id)

@section('content')
@php($estado = $appointment->status?->value)
<div class="wrap">
    <header class="page-head">
        <div>
            <h1>Marcação #{{ $appointment->id }}</h1>
            <p class="sub">{{ $appointment->service?->name ?? 'Serviço não encontrado' }}</p>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('appointments.edit', $appointment->id) }}">Editar</a>
            <a class="btn ghost" href="{{ route('appointments.index') }}">Voltar</a>
        </div>
    </header>

    <div class="page-body narrow">
        <div class="detail" data-tilt>
            <dl>
                <div class="row">
                    <dt>Data</dt>
                    <dd class="big lift-far">{{ $appointment->date?->format('d/m/Y') ?? '-' }}</dd>
                </div>
                <div class="row">
                    <dt>Hora</dt>
                    <dd class="lift">{{ $appointment->time ? substr($appointment->time, 0, 5) : '-' }}</dd>
                </div>
                <div class="row">
                    <dt>Cliente</dt>
                    <dd class="lift">{{ $appointment->customer?->name ?? 'Utilizador não encontrado' }}</dd>
                </div>
                <div class="row">
                    <dt>Barbeiro</dt>
                    <dd class="lift">{{ $appointment->barber?->name ?? 'Barbeiro não encontrado' }}</dd>
                </div>
                <div class="row">
                    <dt>Serviço</dt>
                    <dd class="lift">{{ $appointment->service?->name ?? 'Serviço não encontrado' }}</dd>
                </div>
                <div class="row">
                    <dt>Estado</dt>
                    <dd class="lift">
                        @if ($estado)
                            <span class="badge badge-{{ \Illuminate\Support\Str::slug($estado) }}">{{ $estado }}</span>
                        @else
                            -
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
