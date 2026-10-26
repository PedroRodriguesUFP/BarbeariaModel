<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Status;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        // ---------- KPIs ----------
        $paid = Payment::where('status', 'paid');

        $last30 = DB::table('appointments')
            ->where('date', '>=', $today->copy()->subDays(29)->toDateString())
            ->where('date', '<=', $today->toDateString())
            ->count();

        $cancelled30 = DB::table('appointments')
            ->where('date', '>=', $today->copy()->subDays(29)->toDateString())
            ->where('date', '<=', $today->toDateString())
            ->where('status', Status::Cancelled->value)
            ->count();

        $kpis = [
            'revenueMonth' => (float) (clone $paid)
                ->where('paid_at', '>=', $monthStart)
                ->sum('amount'),
            'revenueTotal' => (float) (clone $paid)->sum('amount'),
            'appointmentsMonth' => DB::table('appointments')
                ->where('date', '>=', $monthStart->toDateString())
                ->count(),
            'appointmentsToday' => DB::table('appointments')
                ->where('date', $today->toDateString())
                ->count(),
            'clients' => User::where('role', UserRole::Client->value)
                ->where('active', true)
                ->count(),
            'barbers' => User::where('role', UserRole::Barber->value)
                ->where('active', true)
                ->count(),
            'cancelRate' => $last30 > 0
                ? round($cancelled30 / $last30 * 100)
                : 0,
        ];

        // ---------- Gráfico: marcações por dia (30 dias) ----------
        $perDayRaw = Appointment::where('date', '>=', $today->copy()->subDays(29)->toDateString())
            ->where('date', '<=', $today->toDateString())
            ->get(['date'])
            ->countBy(fn ($a) => $a->date->format('Y-m-d'));

        $days = [];
        $perDay = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i);
            $days[] = $d->format('d/m');
            $perDay[] = $perDayRaw[$d->format('Y-m-d')] ?? 0;
        }

        // ---------- Gráfico: receita por mês (6 meses) ----------
        $sixMonthsAgo = $today->copy()->startOfMonth()->subMonths(5);

        $revenueRaw = Payment::where('status', 'paid')
            ->where('paid_at', '>=', $sixMonthsAgo)
            ->get(['amount', 'paid_at'])
            ->groupBy(fn ($p) => $p->paid_at->format('Y-m'))
            ->map(fn ($g) => (float) $g->sum('amount'));

        $months = [];
        $revenue = [];
        for ($i = 0; $i < 6; $i++) {
            $m = $sixMonthsAgo->copy()->addMonths($i);
            $months[] = $m->format('m/Y');
            $revenue[] = round($revenueRaw[$m->format('Y-m')] ?? 0, 2);
        }

        // ---------- Gráfico: marcações por barbeiro ----------
        $barbers = User::where('role', UserRole::Barber->value)
            ->withCount([
                'appointmentsAsBarber as total' => fn ($q) => $q->where('status', '!=', Status::Cancelled->value),
            ])
            ->orderByDesc('total')
            ->get();

        // ---------- Gráfico: serviços mais pedidos ----------
        $topServices = DB::table('appointments')
            ->join('services', 'services.id', '=', 'appointments.service_id')
            ->where('appointments.status', '!=', Status::Cancelled->value)
            ->select('services.name', DB::raw('count(*) as total'))
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // ---------- Gráfico: estados ----------
        $byStatus = DB::table('appointments')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusLabels = [
            'pending' => 'Pendentes',
            'confirmed' => 'Confirmadas',
            'completed' => 'Concluídas',
            'cancelled' => 'Canceladas',
        ];

        $charts = [
            'perDay' => ['labels' => $days, 'data' => $perDay],
            'revenue' => ['labels' => $months, 'data' => $revenue],
            'barbers' => [
                'labels' => $barbers->pluck('name')->all(),
                'data' => $barbers->pluck('total')->all(),
            ],
            'services' => [
                'labels' => $topServices->pluck('name')->all(),
                'data' => $topServices->pluck('total')->all(),
            ],
            'status' => [
                'labels' => array_values($statusLabels),
                'data' => array_map(fn ($k) => (int) ($byStatus[$k] ?? 0), array_keys($statusLabels)),
            ],
        ];

        // ---------- Próximas marcações ----------
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
