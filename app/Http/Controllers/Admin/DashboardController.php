<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Status;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Reúne todos os indicadores (KPIs) e dados estatísticos 
     * para alimentar o painel de controlo da administração.
     */
    public function index()
    {
        // 1. Preparação de Datas Base
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $thirtyDaysAgo = $today->copy()->subDays(29);

        // ---------------------------------------------------------
        // KPIs Principais (Indicadores de Performance)
        // ---------------------------------------------------------
        
        // Faturação
        $paidPayments = Payment::where('status', 'paid');
        $revenueTotal = (clone $paidPayments)->sum('amount');
        $revenueMonth = (clone $paidPayments)->where('paid_at', '>=', $monthStart)->sum('amount');

        // Volume de Marcações (Últimos 30 dias para a taxa de cancelamento)
        $appointmentsLast30Days = Appointment::where('date', '>=', $thirtyDaysAgo->toDateString())
            ->where('date', '<=', $today->toDateString());
            
        $totalLast30 = (clone $appointmentsLast30Days)->count();
        $cancelledLast30 = (clone $appointmentsLast30Days)->where('status', Status::Cancelled->value)->count();

        $kpis = [
            'revenueMonth'      => (float) $revenueMonth,
            'revenueTotal'      => (float) $revenueTotal,
            'appointmentsMonth' => Appointment::where('date', '>=', $monthStart->toDateString())->count(),
            'appointmentsToday' => Appointment::where('date', $today->toDateString())->count(),
            'clients'           => User::where('role', UserRole::Client->value)->where('active', true)->count(),
            'barbers'           => User::where('role', UserRole::Barber->value)->where('active', true)->count(),
            // Evita divisão por zero caso seja uma plataforma recém-estreada
            'cancelRate'        => $totalLast30 > 0 ? round(($cancelledLast30 / $totalLast30) * 100) : 0,
        ];

        // ---------------------------------------------------------
        // Dados para Gráficos
        // ---------------------------------------------------------

        // 1. Evolução Diária (Últimos 30 dias)
        // Agrupamos na base de dados para ser mais rápido (boa prática em BDAD)
        $perDayRaw = Appointment::where('date', '>=', $thirtyDaysAgo->toDateString())
            ->where('date', '<=', $today->toDateString())
            ->select('date', DB::raw('count(*) as total'))
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $days = [];
        $perDay = [];
        
        // Preencher o array garantindo que os dias sem marcações aparecem a zero
        for ($i = 29; $i >= 0; $i--) {
            $currentDay = $today->copy()->subDays($i);
            $days[] = $currentDay->format('d/m');
            $perDay[] = $perDayRaw[$currentDay->format('Y-m-d')] ?? 0;
        }

        // 2. Receita Mensal (Últimos 6 meses)
        $sixMonthsAgo = $today->copy()->startOfMonth()->subMonths(5);
        $revenueRaw = Payment::where('status', 'paid')
            ->where('paid_at', '>=', $sixMonthsAgo)
            ->get(['amount', 'paid_at'])
            ->groupBy(fn ($payment) => $payment->paid_at->format('Y-m'))
            ->map(fn ($group) => (float) $group->sum('amount'));

        $months = [];
        $revenue = [];
        for ($i = 0; $i < 6; $i++) {
            $currentMonth = $sixMonthsAgo->copy()->addMonths($i);
            $months[] = $currentMonth->format('m/Y');
            $revenue[] = round($revenueRaw[$currentMonth->format('Y-m')] ?? 0, 2);
        }

        // 3. Performance dos Barbeiros (Marcações não canceladas)
        $barbers = User::where('role', UserRole::Barber->value)
            ->withCount([
                'appointmentsAsBarber as total' => fn ($query) => $query->where('status', '!=', Status::Cancelled->value),
            ])
            ->orderByDesc('total')
            ->get();

        // 4. Top 5 Serviços mais procurados (Substituído DB facade por Eloquent puro)
        $topServices = Service::withCount([
                'appointments as total' => fn ($query) => $query->where('status', '!=', Status::Cancelled->value)
            ])
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // 5. Distribuição de Estados
        $byStatus = Appointment::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusLabels = [
            Status::Pending->value   => 'Pendentes',
            Status::Confirmed->value => 'Confirmadas',
            Status::Completed->value => 'Concluídas',
            Status::Cancelled->value => 'Canceladas',
        ];

        $charts = [
            'perDay'   => ['labels' => $days, 'data' => $perDay],
            'revenue'  => ['labels' => $months, 'data' => $revenue],
            'barbers'  => [
                'labels' => $barbers->pluck('name')->all(),
                'data'   => $barbers->pluck('total')->all(),
            ],
            'services' => [
                'labels' => $topServices->pluck('name')->all(),
                'data'   => $topServices->pluck('total')->all(),
            ],
            'status'   => [
                'labels' => array_values($statusLabels),
                'data'   => array_map(fn ($statusKey) => (int) ($byStatus[$statusKey] ?? 0), array_keys($statusLabels)),
            ],
        ];

        // ---------------------------------------------------------
        // Agenda Futura (Próximas 6 marcações ativas)
        // ---------------------------------------------------------
        $upcoming = Appointment::with(['customer', 'barber', 'service'])
            ->where('date', '>=', $today->toDateString())
            ->whereIn('status', [Status::Pending->value, Status::Confirmed->value])
            ->orderBy('date')
            ->orderBy('time')
            ->limit(6)
            ->get();

        return view('admin.dashboard', compact('kpis', 'charts', 'upcoming'));
    }
}