@extends('layouts.barbearia')

@section('title', 'Novo serviço')

@section('content')
<div class="wrap">
    <header class="page-head">
        <h1>Novo serviço</h1>
    </header>

    <div class="page-body narrow">
        @include('partials.messages')

        <div class="form-card">
            <form action="{{ route('services.store') }}" method="POST">
                @csrf

                <div class="fields">
                    <div class="field">
                        <label for="name">Nome</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required>
                    </div>

                    <div class="field">
                        <label for="description">Descrição</label>
                        <textarea id="description" name="description">{{ old('description') }}</textarea>
                    </div>
                </div>

                <div class="fields two">
                    <div class="field">
                        <label for="price">Preço (€)</label>
                        <input id="price" type="number" step="0.01" min="0" name="price" value="{{ old('price') }}" required>
                    </div>

                    <div class="field">
                        <label for="duration_minutes">Duração (minutos)</label>
                        <input id="duration_minutes" type="number" min="1" name="duration_minutes" value="{{ old('duration_minutes') }}" required>
                    </div>

                    <div class="field full">
                        <label for="category">Categoria</label>
                        <select id="category" name="category">
                            <option value="hair" @selected(old('category') === 'hair')>Cabelo</option>
                            <option value="beard" @selected(old('category') === 'beard')>Barba</option>
                            <option value="hair and beard" @selected(old('category') === 'hair and beard')>Cabelo e barba</option>
                            <option value="fade" @selected(old('category') === 'fade')>Fade</option>
                            <option value="coloring" @selected(old('category') === 'coloring')>Coloração</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn" type="submit">Criar serviço</button>
                    <a class="btn ghost" href="{{ route('services.index') }}">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
