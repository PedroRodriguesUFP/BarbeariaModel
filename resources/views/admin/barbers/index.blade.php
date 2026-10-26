@extends('layouts.barbearia')

@section('title', 'Barbeiros')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
<div class="wrap">
    <header class="page-head">
        <div>
            <h1>Barbeiros</h1>
            <p class="sub">Criar, editar e desativar barbeiros.</p>
        </div>
        <a class="btn" href="{{ route('admin.barbers.create') }}">Novo barbeiro</a>
    </header>

    <div class="page-body">
        @include('partials.messages')

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th><th>Email</th><th>Telefone</th>
                        <th>Serviços</th><th>Marcações</th><th>Estado</th><th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($barbers as $barber)
                        <tr>
                            <td>{{ $barber->name }}</td>
                            <td>{{ $barber->email }}</td>
                            <td>{{ $barber->phone ?? '—' }}</td>
                            <td class="num">{{ $barber->services_count }}</td>
                            <td class="num">{{ $barber->appointments_as_barber_count }}</td>
                            <td>
                                <span class="badge {{ $barber->active ? 'badge-confirmed' : 'badge-cancelled' }}">
                                    {{ $barber->active ? 'ativo' : 'inativo' }}
                                </span>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a class="link" href="{{ route('barbers.calendar', $barber) }}">Calendário</a>
                                    <a class="link" href="{{ route('admin.barbers.edit', $barber) }}">Editar</a>
                                    <form action="{{ route('admin.barbers.destroy', $barber) }}" method="POST"
                                          onsubmit="return confirm('Eliminar {{ e($barber->name) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="link danger" type="submit">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="table-empty" colspan="7">Ainda não existem barbeiros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
