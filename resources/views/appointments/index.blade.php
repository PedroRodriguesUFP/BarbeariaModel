@extends('layouts.barbearia')

@section('title', 'Marcações')

@section('content')
<div class="wrap">
    <header class="page-head">
        <div>
            <h1>Marcações</h1>
            <p class="sub">Quem vem, com que barbeiro e a que horas.</p>
        </div>
        <a class="btn" href="{{ route('appointments.create') }}">Nova marcação</a>
    </header>

    <div class="page-body">
        @include('partials.messages')

        {{-- Filtros: vão no URL (?q=...&estado=...), por isso a paginação não os perde. --}}
        <form method="GET" action="{{ route('appointments.index') }}" class="form-card" style="margin-bottom: 1rem;">
            <div class="fields two">
                <div class="field">
                    <label for="q">Pesquisar</label>
                    <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Cliente, barbeiro ou serviço">
                </div>

                <div class="field">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        <option value="">Todos</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('estado') === $status->value)>{{ $status->value }}</option>
                        @endforeach
                    </select>
                </div>

                @unless (auth()->user()->isBarber())
                    <div class="field">
                        <label for="barbeiro">Barbeiro</label>
                        <select id="barbeiro" name="barbeiro">
                            <option value="">Todos</option>
                            @foreach ($barbers as $barber)
                                <option value="{{ $barber->id }}" @selected((int) request('barbeiro') === $barber->id)>{{ $barber->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endunless

                <div class="field">
                    <label for="ordem">Ordenar por</label>
                    <select id="ordem" name="ordem">
                        <option value="data" @selected(request('ordem', 'data') === 'data')>Data</option>
                        <option value="estado" @selected(request('ordem') === 'estado')>Estado</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn small" type="submit">Filtrar</button>
                <a class="btn ghost small" href="{{ route('appointments.index') }}">Limpar</a>
            </div>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Data e hora</th>
                        <th>Cliente</th>
                        <th>Barbeiro</th>
                        <th>Serviço</th>
                        <th>Estado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($appointments as $appointment)
                        @php($estado = $appointment->status?->value)
                        <tr>
                            <td class="num">
                                {{ $appointment->date?->format('d/m/Y') ?? '-' }}
                                @if ($appointment->time)
                                    {{ substr($appointment->time, 0, 5) }}
                                @endif
                            </td>
                            <td>{{ $appointment->customerDisplayName() }}</td>
                            <td>{{ $appointment->barber?->name ?? 'Barbeiro não encontrado' }}</td>
                            <td>{{ $appointment->service?->name ?? 'Serviço não encontrado' }}</td>
                            <td>
                                @if ($estado)
                                    <span class="badge badge-{{ \Illuminate\Support\Str::slug($estado) }}">{{ $estado }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <div class="row-actions">
                                    @can('view', $appointment)
                                        <a class="link" href="{{ route('appointments.show', $appointment) }}">Ver</a>
                                    @endcan
                                    @if ($appointment->canBePaidBy(auth()->user()))
                                        <form action="{{ route('payments.checkout', $appointment) }}" method="POST">
                                            @csrf
                                            <button class="link" type="submit">Pagar com cartão</button>
                                        </form>
                                    @endif
                                    @can('update', $appointment)
                                        <a class="link" href="{{ route('appointments.edit', $appointment) }}">Editar</a>
                                    @endcan
                                    @can('delete', $appointment)
                                        <form
                                            action="{{ route('appointments.destroy', $appointment) }}"
                                            method="POST"
                                            onsubmit="return confirm('Eliminar esta marcação?')"
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
                            <td class="table-empty" colspan="6">
                                Ainda não existem marcações.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $appointments->links() }}
    </div>
</div>
@endsection
