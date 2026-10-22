@extends('layouts.barbearia')

@section('title', 'Recuperar password')

@section('content')
<div class="wrap">
    <header class="page-head">
        <div>
            <h1>Recuperar password</h1>
            <p class="sub">Indica o teu email e enviamos-te um link para repor a password.</p>
        </div>
    </header>

    <div class="page-body narrow">
        @include('partials.messages')

        <div class="form-card">
            <form action="{{ route('password.email') }}" method="POST">
                @csrf

                <div class="fields">
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="@error('email') border-red-500 @enderror">
                        @error('email')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn" type="submit">
                        Enviar link de recuperação
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