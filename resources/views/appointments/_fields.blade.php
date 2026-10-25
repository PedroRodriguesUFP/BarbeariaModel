{{-- Campos partilhados por create e edit. Espera: $barbers, $services, $customers, ?$appointment --}}
@php
    $appointment = $appointment ?? null;
    $selectedService = old('service_id', $appointment?->service_id);
    $selectedBarber = old('barber_id', $appointment?->barber_id);
@endphp

<div class="fields two">

    @if (! $appointment && auth()->user()->isAdmin())
        <div class="field full">
            <label for="customer_id">Cliente</label>
            <select id="customer_id" name="customer_id" required>
                <option value="">Escolher cliente…</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                        {{ $customer->name }} ({{ $customer->email }})
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="field full">
        <label for="service_id">Serviço</label>
        <select id="service_id" name="service_id" required>
            <option value="">Escolher serviço…</option>
            @foreach ($services as $service)
                <option value="{{ $service->id }}" @selected($selectedService == $service->id)>
                    {{ $service->name }}
                    · {{ $service->duration_minutes }} min
                    · {{ number_format($service->price, 2, ',', ' ') }} €
                </option>
            @endforeach
        </select>
    </div>

    <div class="field full">
        <label for="barber_id">Barbeiro</label>
        <select id="barber_id" name="barber_id" required>
            <option value="">Escolher barbeiro…</option>
            @foreach ($barbers as $barber)
                <option
                    value="{{ $barber->id }}"
                    data-services="{{ $barber->services->pluck('id')->implode(',') }}"
                    @selected($selectedBarber == $barber->id)
                >
                    {{ $barber->name }}
                </option>
            @endforeach
        </select>
        <p class="hint" id="barber_hint" hidden>
            Só aparecem barbeiros que fazem o serviço escolhido.
        </p>
    </div>

    <div class="field">
        <label for="date">Data</label>
        <input
            id="date" name="date" type="date"
            min="{{ now()->toDateString() }}"
            value="{{ old('date', $appointment?->date?->format('Y-m-d')) }}"
            required
        >
    </div>

    <div class="field">
        <label for="time">Hora</label>
        <input
            id="time" name="time" type="time" step="900"
            value="{{ old('time', $appointment?->time ? substr($appointment->time, 0, 5) : '') }}"
            required
        >
    </div>
</div>

<script>
    // Mostra só os barbeiros que fazem o serviço escolhido.
    (function () {
        const service = document.getElementById('service_id');
        const barber = document.getElementById('barber_id');
        const hint = document.getElementById('barber_hint');
        if (!service || !barber) return;

        function filter() {
            const id = service.value;
            let selectedStillValid = barber.value === '';

            Array.from(barber.options).forEach(function (opt) {
                if (!opt.value) return;
                const ids = (opt.dataset.services || '').split(',');
                const ok = !id || ids.includes(id);
                opt.hidden = !ok;
                opt.disabled = !ok;
                if (ok && opt.value === barber.value) selectedStillValid = true;
            });

            if (!selectedStillValid) barber.value = '';
            hint.hidden = !id;
        }

        service.addEventListener('change', filter);
        filter();
    })();
</script>
