@extends('layouts.barbearia')

@section('title', 'Barbeiros')

@section('content')
<div class="wrap">
    <header class="page-head">
        <div>
            <h1>Barbeiros</h1>
            <p class="sub">Escolhe um barbeiro para ver a agenda dele.</p>
        </div>
    </header>

    <div class="page-body">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Nome</th><th>Email</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    @forelse ($barbers as $barber)
                        <tr>
                            <td>{{ $barber->name }}</td>
                            <td>{{ $barber->email }}</td>
                            <td><a class="link" href="{{ route('barbers.calendar', $barber->id) }}">Ver calendário</a></td>
                        </tr>
                    @empty
                        <tr><td class="table-empty" colspan="3">Ainda não existem barbeiros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection