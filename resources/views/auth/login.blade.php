@extends('layouts.barbearia')

@section('title', 'Iniciar sessão')

@section('content')
<div class="wrap">
    <header class="page-head">
        <div>
            <h1>Iniciar sessão</h1>
            <p class="sub">Entre para marcar, pagar e avaliar.</p>
        </div>
    </header>

    <div class="page-body narrow">
        @include('partials.messages', ['showErrors' => false])

        <div class="form-card">
            <form action="{{ route('login') }}" method="POST">
                @csrf

                <div class="fields">
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="@error('email') border-red-500 @enderror">
                        @error('email')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password">Palavra-passe</label>
                        <input id="password" name="password" type="password" required class="@error('password') border-red-500 @enderror">
                        @error('password')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn" type="submit">Entrar</button>
                    <a class="btn ghost" href="{{ route('home') }}">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
