@extends('layouts.barbearia')

@section('title', 'Repor password')

@section('content')
<div class="wrap">
    <header class="page-head">
        <div>
            <h1>Repor password</h1>
            <p class="sub">Escolhe uma nova password para a tua conta.</p>
        </div>
    </header>

    <div class="page-body narrow">
        @include('partials.messages', ['showErrors' => false])

        <div class="form-card">
            <form action="{{ route('password.update') }}" method="POST">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div class="fields">
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autofocus class="@error('email') border-red-500 @enderror">
                        @error('email')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password">Nova palavra-passe</label>
                        <input id="password" name="password" type="password" required class="@error('password') border-red-500 @enderror">
                        @error('password')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Confirmar palavra-passe</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn" type="submit">
                        Repor password
                    </button>

                    <a class="btn ghost" href="{{ route('login') }}">
                        Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection