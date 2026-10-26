@extends('layouts.barbearia')

@section('title', 'Contas')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
@php
    $roleLabels = ['admin' => 'Admin', 'barber' => 'Barbeiro', 'client' => 'Cliente'];
@endphp
<div class="wrap">
    <header class="page-head">
        <div>
            <h1>Contas</h1>
            <p class="sub">Todos os utilizadores. Muda o role, desativa ou elimina.</p>
        </div>
        <a class="btn" href="{{ route('admin.users.create') }}">Nova conta</a>
    </header>

    <div class="page-body">
        @include('partials.messages')

        <form class="filters" method="GET" action="{{ route('admin.users.index') }}">
            <div class="field">
                <label for="q">Pesquisar</label>
                <input id="q" name="q" type="search" value="{{ $q }}" placeholder="Nome ou email">
            </div>
            <div class="field">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="">Todos</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r->value }}" @selected($role === $r->value)>{{ $roleLabels[$r->value] }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn small" type="submit">Filtrar</button>
            <a class="btn small ghost" href="{{ route('admin.users.index') }}">Limpar</a>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Nome</th><th>Email</th><th>Role</th><th>Estado</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        @php($self = $user->id === auth()->id())
                        <tr>
                            <td>{{ $user->name }} @if ($self)<small>(tu)</small>@endif</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if ($self)
                                    {{ $roleLabels[$user->role->value] }}
                                @else
                                    <form action="{{ route('admin.users.update', $user) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <select class="inline-select" name="role" onchange="this.form.submit()" aria-label="Role de {{ $user->name }}">
                                            @foreach ($roles as $r)
                                                <option value="{{ $r->value }}" @selected($user->role === $r)>{{ $roleLabels[$r->value] }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $user->active ? 'badge-confirmed' : 'badge-cancelled' }}">
                                    {{ $user->active ? 'ativa' : 'desativada' }}
                                </span>
                            </td>
                            <td>
                                @unless ($self)
                                    <div class="row-actions">
                                        <form action="{{ route('admin.users.update', $user) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="active" value="{{ $user->active ? 0 : 1 }}">
                                            <button class="link" type="submit">{{ $user->active ? 'Desativar' : 'Ativar' }}</button>
                                        </form>
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                              onsubmit="return confirm('Eliminar {{ e($user->name) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="link danger" type="submit">Eliminar</button>
                                        </form>
                                    </div>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td class="table-empty" colspan="5">Nenhuma conta encontrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
