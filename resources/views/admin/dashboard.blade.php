@extends('layouts.barbearia')

@section('title', 'Painel')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
<div class="wrap">
    <header class="page-head">
        <div>
            <h1>Painel</h1>
            <p class="sub">Visão geral da barbearia.</p>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('admin.barbers.create') }}">Novo barbeiro</a>
            <a class="btn ghost" href="{{ route('admin.users.index') }}">Gerir contas</a>
        </div>
    </header>

    <div class="page-body">
        @include('partials.messages')

        <div class="kpis">
            <div class="kpi accent">
                <div class="label">Receita este mês</div>
                <div class="value">{{ number_format($kpis['revenueMonth'], 2, ',', ' ') }} €</div>
            </div>
            <div class="kpi">
                <div class="label">Receita total</div>
                <div class="value">{{ number_format($kpis['revenueTotal'], 2, ',', ' ') }} €</div>
            </div>
            <div class="kpi">
                <div class="label">Marcações hoje</div>
                <div class="value">{{ $kpis['appointmentsToday'] }}</div>
            </div>
            <div class="kpi">
                <div class="label">Marcações este mês</div>
                <div class="value">{{ $kpis['appointmentsMonth'] }}</div>
            </div>
            <div class="kpi">
                <div class="label">Clientes ativos</div>
                <div class="value">{{ $kpis['clients'] }}</div>
            </div>
            <div class="kpi">
                <div class="label">Barbeiros ativos</div>
                <div class="value">{{ $kpis['barbers'] }}</div>
            </div>
            <div class="kpi">
                <div class="label">Cancelamentos (30 dias)</div>
                <div class="value">{{ $kpis['cancelRate'] }}%</div>
            </div>
        </div>

        <div class="charts">
            <div class="chart-card wide">
                <h2>Marcações por dia · últimos 30 dias</h2>
                <div class="chart-box"><canvas id="chart-per-day" aria-label="Marcações por dia" role="img"></canvas></div>
            </div>

            <div class="chart-card">
                <h2>Receita por mês</h2>
                <div class="chart-box"><canvas id="chart-revenue" aria-label="Receita por mês" role="img"></canvas></div>
            </div>

            <div class="chart-card">
                <h2>Marcações por barbeiro</h2>
                <div class="chart-box"><canvas id="chart-barbers" aria-label="Marcações por barbeiro" role="img"></canvas></div>
            </div>

            <div class="chart-card">
                <h2>Serviços mais pedidos</h2>
                <div class="chart-box"><canvas id="chart-services" aria-label="Serviços mais pedidos" role="img"></canvas></div>
            </div>

            <div class="chart-card">
                <h2>Estado das marcações</h2>
                <div class="chart-box"><canvas id="chart-status" aria-label="Estado das marcações" role="img"></canvas></div>
            </div>
        </div>

        <h2 class="section-title">Próximas marcações</h2>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Data e hora</th><th>Cliente</th><th>Barbeiro</th><th>Serviço</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    @forelse ($upcoming as $a)
                        @php($estado = $a->status?->value)
                        <tr>
                            <td class="num">
                                {{ $a->date?->format('d/m/Y') }}
                                {{ $a->time ? substr($a->time, 0, 5) : '' }}
                            </td>
                            <td>{{ $a->customerDisplayName() }}</td>
                            <td>{{ $a->barber?->name ?? '—' }}</td>
                            <td>{{ $a->service?->name ?? '—' }}</td>
                            <td><span class="badge badge-{{ \Illuminate\Support\Str::slug($estado) }}">{{ $estado }}</span></td>
                        </tr>
                    @empty
                        <tr><td class="table-empty" colspan="5">Sem marcações futuras.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const data = @json($charts);

        if (typeof Chart === 'undefined') return;

        const RED = '#c23b2e', SKY = '#8fb3d1', STEEL = '#566678', INK = '#12243a', GOLD = '#d9a441';
        const text = getComputedStyle(document.body).color;

        Chart.defaults.color = text;
        Chart.defaults.font.family = 'Archivo, system-ui, sans-serif';
        Chart.defaults.borderColor = 'rgba(128,128,128,.25)';
        Chart.defaults.maintainAspectRatio = false;

        const axisInt = { beginAtZero: true, ticks: { precision: 0 } };

        function empty(id, d) {
            if (d.data.some(function (v) { return v > 0; })) return false;
            const box = document.getElementById(id).parentElement;
            box.innerHTML = '<p class="chart-empty">Ainda sem dados.</p>';
            return true;
        }

        function make(id, config) {
            const d = config.data.datasets[0];
            if (empty(id, { data: d.data })) return;
            new Chart(document.getElementById(id), config);
        }

        make('chart-per-day', {
            type: 'line',
            data: {
                labels: data.perDay.labels,
                datasets: [{ label: 'Marcações', data: data.perDay.data, borderColor: RED, backgroundColor: 'rgba(194,59,46,.15)', fill: true, tension: .3, pointRadius: 2 }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: axisInt, x: { ticks: { maxTicksLimit: 10 } } } }
        });

        make('chart-revenue', {
            type: 'bar',
            data: {
                labels: data.revenue.labels,
                datasets: [{ label: 'Receita (€)', data: data.revenue.data, backgroundColor: RED }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });

        make('chart-barbers', {
            type: 'bar',
            data: {
                labels: data.barbers.labels,
                datasets: [{ label: 'Marcações', data: data.barbers.data, backgroundColor: SKY }]
            },
            options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: axisInt } }
        });

        make('chart-services', {
            type: 'doughnut',
            data: {
                labels: data.services.labels,
                datasets: [{ data: data.services.data, backgroundColor: [RED, SKY, STEEL, GOLD, INK], borderWidth: 0 }]
            },
            options: { plugins: { legend: { position: 'bottom' } } }
        });

        make('chart-status', {
            type: 'doughnut',
            data: {
                labels: data.status.labels,
                datasets: [{ data: data.status.data, backgroundColor: [GOLD, SKY, '#3f8f5b', RED], borderWidth: 0 }]
            },
            options: { plugins: { legend: { position: 'bottom' } } }
        });
    })();
</script>
@endsection
