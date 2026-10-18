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
                            <td>{{ $appointment->customer?->name ?? 'Utilizador não encontrado' }}</td>
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
                                    <a class="link" href="{{ route('appointments.show', $appointment->id) }}">Ver</a>
                                    <a class="link" href="{{ route('appointments.edit', $appointment->id) }}">Editar</a>
                                    <form
                                        action="{{ route('appointments.destroy', $appointment->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Eliminar esta marcação?')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button class="link danger" type="submit">Eliminar</button>
                                    </form>
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
    </div>
</div>
@endsection
