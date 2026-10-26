@extends('layouts.barbearia')

@section('title', 'Editar marcação')

@section('content')
<div class="wrap">
    <header class="page-head">
        <h1>Editar marcação</h1>
    </header>

    <div class="page-body narrow">
        @include('partials.messages')

        <div class="form-card">
            <form action="{{ route('appointments.update', $appointment->id) }}" method="POST">
                @csrf
                @method('PUT')

                @include('appointments._fields')

                <div class="form-actions">
                    <button class="btn" type="submit">Guardar alterações</button>
                    <a class="btn ghost" href="{{ route('appointments.index') }}">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
