{{-- Espera: $services, ?$barber --}}
@php($barber = $barber ?? null)

<div class="fields two">
    <div class="field">
        <label for="name">Nome</label>
        <input id="name" name="name" type="text" value="{{ old('name', $barber?->name) }}" required>
    </div>

    <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $barber?->email) }}" required>
    </div>

    <div class="field">
        <label for="phone">Telefone</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $barber?->phone) }}">
    </div>

    <div class="field">
        <label for="password">Palavra-passe</label>
        <input id="password" name="password" type="password" autocomplete="new-password" @required(! $barber)>
        @if ($barber)
            <p class="hint">Deixa vazio para manter a atual.</p>
        @endif
    </div>

    <div class="field">
        <label for="password_confirmation">Confirmar palavra-passe</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" @required(! $barber)>
    </div>

    <div class="field full">
        <label for="bio">Bio</label>
        <textarea id="bio" name="bio">{{ old('bio', $barber?->bio) }}</textarea>
    </div>

    <div class="field full">
        <label>Serviços que realiza</label>
        <div class="checks">
            @php($selected = collect(old('services', $barber?->services?->pluck('id')->all() ?? [])))
            @foreach ($services as $service)
                <label>
                    <input type="checkbox" name="services[]" value="{{ $service->id }}" @checked($selected->contains($service->id))>
                    {{ $service->name }}
                </label>
            @endforeach
        </div>
        @if ($services->isEmpty())
            <p class="hint">Ainda não há serviços. Cria primeiro em Serviços.</p>
        @endif
    </div>

    @if ($barber)
        <div class="field full">
            <div class="checks">
                <label>
                    <input type="checkbox" name="active" value="1" @checked(old('active', $barber->active))>
                    Conta ativa
                </label>
            </div>
        </div>
    @else
        <div class="field full">
            <div class="checks">
                <label>
                    <input type="checkbox" name="default_hours" value="1" @checked(old('default_hours', true))>
                    Criar horário padrão (Seg–Sex 09–13 e 14–18, Sáb 09–13)
                </label>
            </div>
            <p class="hint">Sem horário o barbeiro não aparece disponível para marcações.</p>
        </div>
    @endif
</div>
