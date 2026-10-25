@extends('layouts.barbearia')

@section('title', 'Novo barbeiro')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
<div class="wrap">
    <header class="page-head"><h1>Novo barbeiro</h1></header>

    <div class="page-body narrow">
        @include('partials.messages')

        <div class="form-card">
            <form action="{{ route('admin.barbers.store') }}" method="POST">
                @csrf
                @include('admin.barbers._form')

                <div class="form-actions">
                    <button class="btn" type="submit">Criar barbeiro</button>
                    <a class="btn ghost" href="{{ route('admin.barbers.index') }}">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
