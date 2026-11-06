<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Models\Appointment;
use App\Models\ScheduleBlock;
use App\Models\WorkingHour;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BarberController extends Controller
{
    public function dashboard(Request $request)
    {
        $barber = $request->user();
        $now = Carbon::now();
        $today = Carbon::today();

        // Marcações de hoje, por ordem de hora
        $todayAppointments = Appointment::with(['customer', 'service'])
            ->where('barber_id', $barber->id)
            ->whereDate('date', $today)
            ->orderBy('time')
            ->get();

        // Marcações ativas de hoje em diante
        $activeAppointments = Appointment::with(['customer', 'service'])
            ->where('barber_id', $barber->id)
            ->whereDate('date', '>=', $today)
            ->whereIn('status', [Status::Pending, Status::Confirmed])
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        // Só ficam as que ainda não passaram
        $upcomingAppointments = [];

        foreach ($activeAppointments as $appointment) {
            $isToday = $appointment->date->isSameDay($today);
            $alreadyPassed = $isToday
                && $appointment->time !== null
                && $appointment->time < $now->format('H:i:s');

            if (! $alreadyPassed) {
                $upcomingAppointments[] = $appointment;
            }
        }

        $nextAppointment = $upcomingAppointments[0] ?? null;

        $stats = [
            'today'     => $todayAppointments->count(),
            'upcoming'  => count($upcomingAppointments),
            'completed' => $todayAppointments->where('status', Status::Completed)->count(),
            'pending'   => $todayAppointments->where('status', Status::Pending)->count(),
        ];

        $hour = (int) $now->format('H');

        if ($hour < 12) {
            $greeting = 'Bom dia';
        } elseif ($hour < 20) {
            $greeting = 'Boa tarde';
        } else {
            $greeting = 'Boa noite';
        }

        return view('barber.dashboard', compact(
            'barber',
            'greeting',
            'stats',
            'nextAppointment',
            'todayAppointments'
        ));
    }

    public function availability(Request $request)
    {
        $barber = $request->user();

        $workingHours = WorkingHour::where('user_id', $barber->id)
            ->orderBy('day_of_week')
            ->get()
            ->keyBy('day_of_week');

        $blocks = ScheduleBlock::where('user_id', $barber->id)
            ->whereDate('date', '>=', Carbon::today())
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $daysOfWeek = [
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
            0 => 'Domingo',
        ];

        return view('barber.availability', compact('barber', 'workingHours', 'blocks', 'daysOfWeek'));
    }

    public function updateWorkingHours(Request $request)
    {
        $barber = $request->user();

        $validated = $request->validate([
            'days'              => ['required', 'array'],
            'days.*.active'     => ['nullable', 'boolean'],
            'days.*.start_time' => ['nullable', 'date_format:H:i'],
            'days.*.end_time'   => ['nullable', 'date_format:H:i'],
        ]);

        foreach ($validated['days'] as $dayOfWeek => $dayData) {
            $dayOfWeek = (int) $dayOfWeek;

            $isActive = ! empty($dayData['active']);
            $startTime = $dayData['start_time'] ?? null;
            $endTime = $dayData['end_time'] ?? null;

            $hasValidTimes = $startTime && $endTime && $endTime > $startTime;

            if ($isActive && $hasValidTimes) {
                WorkingHour::updateOrCreate(
                    ['user_id' => $barber->id, 'day_of_week' => $dayOfWeek],
                    ['start_time' => $startTime, 'end_time' => $endTime]
                );
            } else {
                // Dia desligado ou horas inválidas: o barbeiro não trabalha nesse dia
                WorkingHour::where('user_id', $barber->id)
                    ->where('day_of_week', $dayOfWeek)
                    ->delete();
            }
        }

        return redirect()->route('barber.availability')
            ->with('success', 'Horário semanal atualizado com sucesso.');
    }

    public function storeBlock(Request $request)
    {
        $barber = $request->user();
        $isAllDay = $request->boolean('all_day');

        $timeRule = $isAllDay ? 'nullable' : 'required';

        $validated = $request->validate([
            'date'       => ['required', 'date', 'after_or_equal:today'],
            'all_day'    => ['nullable', 'boolean'],
            'start_time' => [$timeRule, 'date_format:H:i'],
            'end_time'   => [$timeRule, 'date_format:H:i', 'after:start_time'],
            'reason'     => ['nullable', 'string', 'max:140'],
        ]);

        if ($isAllDay) {
            $startTime = '00:00:00';
            $endTime = '23:59:59';
        } else {
            $startTime = $validated['start_time'];
            $endTime = $validated['end_time'];
        }

        ScheduleBlock::create([
            'user_id'    => $barber->id,
            'date'       => $validated['date'],
            'all_day'    => $isAllDay,
            'start_time' => $startTime,
            'end_time'   => $endTime,
            'reason'     => $validated['reason'] ?? 'Indisponibilidade',
        ]);

        return redirect()->route('barber.availability')
            ->with('success', 'Bloqueio registado na agenda.');
    }

    public function destroyBlock(Request $request, ScheduleBlock $block)
    {
        $barber = $request->user();

        if ($block->user_id !== $barber->id) {
            abort(403, 'Não tens permissão para remover este bloqueio.');
        }

        $block->delete();

        return redirect()->route('barber.availability')
            ->with('success', 'Bloqueio removido.');
    }

    public function services(Request $request)
    {
        $barber = $request->user();

        $services = $barber->services()
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('barber.services', compact('barber', 'services'));
    }

    public function reviews(Request $request)
    {
        $barber = $request->user();

        // Só existem avaliações diretas ao barbeiro (reviewable_type = User)
        $reviews = $barber->reviewsReceived()
            ->with('reviewer')
            ->latest()
            ->get();

        $totalReviews = $reviews->count();
        $averageRating = 0;

        if ($totalReviews > 0) {
            $averageRating = round($reviews->avg('rating'), 1);
        }

        return view('barber.reviews', compact('barber', 'reviews', 'totalReviews', 'averageRating'));
    }
}