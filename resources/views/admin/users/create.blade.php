@extends('layouts.barbearia')

@section('title', 'Nova conta')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
@php($roleLabels = ['admin' => 'Admin', 'barber' => 'Barbeiro', 'client' => 'Cliente'])
<div class="wrap">
    <header class="page-head"><h1>Nova conta</h1></header>

    <div class="page-body narrow">
        @include('partials.messages')

        <div class="form-card">
            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf

                <div class="fields two">
                    <div class="field">
                        <label for="name">Nome</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                    </div>
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                    </div>
                    <div class="field">
                        <label for="phone">Telefone</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone') }}">
                    </div>
                    <div class="field">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            @foreach ($roles as $r)
                                <option value="{{ $r->value }}" @selected(old('role', 'client') === $r->value)>{{ $roleLabels[$r->value] }}</option>
                            @endforeach
                        </select>
                        <p class="hint">Para barbeiros usa Barbeiros → Novo (cria também serviços e horário).</p>
                    </div>
                    <div class="field">
                        <label for="password">Palavra-passe</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" required>
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Confirmar palavra-passe</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn" type="submit">Criar conta</button>
                    <a class="btn ghost" href="{{ route('admin.users.index') }}">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
