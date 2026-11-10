@extends('layouts.barbearia')

@section('title', 'Criar conta')

@section('content')
<div class="wrap">
    <header class="page-head">
        <div>
            <h1>Criar conta</h1>
            <p class="sub">Registe-se para marcar, pagar e avaliar.</p>
        </div>
    </header>

    <div class="page-body narrow">
        @include('partials.messages', ['showErrors' => false])

        <div class="form-card">
            <form action="{{ route('register') }}" method="POST">
                @csrf

                <div class="fields">
                    <div class="field">
                        <label for="name">Nome</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus>
                        @error('name')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                        @error('email')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="phone">Telemóvel (opcional)</label>
                        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}">
                        @error('phone')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password">Palavra-passe</label>
                        <input id="password" name="password" type="password" required>
                        @error('password')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Confirmar palavra-passe</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required>
                    </div>
                </div>

                <div class="form-actions" style="margin-top: 16px;">
                    <button class="btn" type="submit">Criar conta</button>
                    <a class="btn ghost" href="{{ route('login') }}">Já tenho conta</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
