@extends('layouts.barbearia')

@section('title', 'Nova marcação')

@section('content')
<div class="wrap">
    <header class="page-head">
        <h1>Nova marcação</h1>
    </header>

    <div class="page-body narrow">
        @include('partials.messages')

        <div class="form-card">
            <form action="{{ route('appointments.store') }}" method="POST">
                @csrf

                @include('appointments._fields')

                <div class="form-actions">
                    <button class="btn" type="submit">Criar marcação</button>
                    <a class="btn ghost" href="{{ route('appointments.index') }}">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
