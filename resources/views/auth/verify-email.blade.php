@extends('layouts.barbearia')

@section('title', 'Verificar email')

@section('content')
<div class="wrap">
    <header class="page-head">
        <div>
            <h1>Verificar endereço de email</h1>
            <p class="sub">Confirma o teu email para continuares a usar a tua conta.</p>
        </div>
    </header>

    <div class="page-body narrow">
        @if (session('status') === 'verification-link-sent')
            <p class="flash" role="status">Enviámos um novo link de verificação para o teu email.</p>
        @elseif (session('status') === 'email-verified')
            <p class="flash" role="status">Email verificado com sucesso.</p>
        @endif

        <div class="form-card">
            <p>
                Enviámos um link de confirmação para
                <strong>{{ auth()->user()->email }}</strong>.
                Abre o email e segue as instruções.
            </p>

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button class="btn" type="submit">Reenviar email de verificação</button>
            </form>

            <form method="POST" action="{{ route('logout') }}" style="margin-top: 1rem;">
                @csrf
                <button class="btn ghost" type="submit">Sair</button>
            </form>
        </div>
    </div>
</div>
@endsection
