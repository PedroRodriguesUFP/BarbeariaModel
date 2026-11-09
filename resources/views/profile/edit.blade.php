@extends('layouts.barbearia')

@section('title', 'Conta')

@section('content')
<div class="wrap">
    <header class="page-head">
        <div>
            <h1>Conta</h1>
            <p class="sub">Atualiza os teus dados e a palavra-passe.</p>
        </div>
    </header>

    <div class="page-body narrow">
        @include('partials.messages', ['showErrors' => false])

        <div class="form-card">
            <h2>Dados da conta</h2>

            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PATCH')

                <div class="fields">
                    <div class="field">
                        <label for="name">Nome</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name" class="@error('name') border-red-500 @enderror">
                        @error('name')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email" class="@error('email') border-red-500 @enderror">
                        @error('email')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn" type="submit">Guardar dados</button>
                </div>
            </form>
        </div>

        <div class="form-card">
            <h2>Alterar palavra-passe</h2>

            <form action="{{ route('profile.password.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="fields">
                    @if ($user->password !== null)
                        <div class="field">
                            <label for="current_password">Palavra-passe atual</label>
                            <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="@error('current_password', 'updatePassword') border-red-500 @enderror">
                            @error('current_password', 'updatePassword')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif

                    <div class="field">
                        <label for="password">Nova palavra-passe</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password" class="@error('password', 'updatePassword') border-red-500 @enderror">
                        @error('password', 'updatePassword')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Confirmar nova palavra-passe</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn" type="submit">Alterar palavra-passe</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
