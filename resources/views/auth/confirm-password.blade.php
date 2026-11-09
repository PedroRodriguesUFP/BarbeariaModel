@extends('layouts.barbearia')

@section('title', 'Confirmar password')

@section('content')
<div class="wrap">
    <header class="page-head">
        <div>
            <h1>Confirmar password</h1>
            <p class="sub">Confirma a tua palavra-passe para continuar.</p>
        </div>
    </header>

    <div class="page-body narrow">
        @include('partials.messages', ['showErrors' => false])

        <div class="form-card">
            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf

                <div class="fields">
                    <div class="field">
                        <label for="password">Palavra-passe</label>
                        <input id="password" name="password" type="password" required autofocus autocomplete="current-password" class="@error('password') border-red-500 @enderror">
                        @error('password')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn" type="submit">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
