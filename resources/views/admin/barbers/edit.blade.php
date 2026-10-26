@extends('layouts.barbearia')

@section('title', 'Editar barbeiro')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
<div class="wrap">
    <header class="page-head"><h1>Editar {{ $barber->name }}</h1></header>

    <div class="page-body narrow">
        @include('partials.messages')

        <div class="form-card">
            <form action="{{ route('admin.barbers.update', $barber) }}" method="POST">
                @csrf
                @method('PUT')
                @include('admin.barbers._form')

                <div class="form-actions">
                    <button class="btn" type="submit">Guardar alterações</button>
                    <a class="btn ghost" href="{{ route('admin.barbers.index') }}">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
