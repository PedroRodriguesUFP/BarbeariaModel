@extends('layouts.barbearia')

@section('title', 'Editar serviço')

@section('content')
<div class="wrap">
    <header class="page-head">
        <h1>Editar serviço</h1>
    </header>

    <div class="page-body narrow">
        @include('partials.messages')

        <div class="form-card">
            <form action="{{ route('services.update', $service) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="fields">
                    <div class="field">
                        <label for="name">Nome</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $service->name) }}" required>
                    </div>

                    <div class="field">
                        <label for="description">Descrição</label>
                        <textarea id="description" name="description">{{ old('description', $service->description) }}</textarea>
                    </div>
                </div>

                <div class="fields two">
                    <div class="field">
                        <label for="price">Preço (€)</label>
                        <input id="price" type="number" step="0.01" min="0" name="price" value="{{ old('price', $service->price) }}" required>
                    </div>

                    <div class="field">
                        <label for="duration_minutes">Duração (minutos)</label>
                        <input id="duration_minutes" type="number" min="1" name="duration_minutes" value="{{ old('duration_minutes', $service->duration_minutes) }}" required>
                    </div>

                    <div class="field full">
                        <label for="category">Categoria</label>
                        <select id="category" name="category">
                            <option value="hair" @selected(old('category', $service->category->value) === 'hair')>Cabelo</option>
                            <option value="beard" @selected(old('category', $service->category->value) === 'beard')>Barba</option>
                            <option value="hair and beard" @selected(old('category', $service->category->value) === 'hair and beard')>Cabelo e barba</option>
                            <option value="fade" @selected(old('category', $service->category->value) === 'fade')>Fade</option>
                            <option value="coloring" @selected(old('category', $service->category->value) === 'coloring')>Coloração</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn" type="submit">Guardar alterações</button>
                    <a class="btn ghost" href="{{ route('services.index') }}">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
