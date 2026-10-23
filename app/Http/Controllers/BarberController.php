<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Models\Appointment;
use App\Models\Review;
use App\Models\ScheduleBlock;
use App\Models\WorkingHour;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BarberController extends Controller
{
    public function dashboard(Request $request)
    {
        $barber =$request->user();
        $today = Carbon::today();$now = Carbon::now();

        $todayAppointments = Appointment::with(['customer', 'service'])
            ->where('barber_id', $barber->id)
            ->whereDate('starts_at', $today)
            ->orderBy('starts_at')
            ->get();

        $upcomingCount = Appointment::where('barber_id',$barber->id)
            ->where('starts_at', '>', $now)
            ->whereIn('status', [Status::Pending, Status::Confirmed])
            ->count();

        $nextAppointment = Appointment::with(['customer', 'service'])
            ->where('barber_id', $barber->id)
            ->where('starts_at', '>=', $now)
            ->whereIn('status', [Status::Pending, Status::Confirmed])
            ->orderBy('starts_at')
            ->first();

        $stats = [
            'today'     => $todayAppointments->count(),
            'upcoming'  => $upcomingCount,
            'completed' => $todayAppointments->where('status', Status::Completed)->count(),
            'pending'   => $todayAppointments->where('status', Status::Pending)->count(),
        ];

        $hour = (int) $now->format('H');$greeting = $hour < 12 ? 'Bom dia' : ($hour < 20 ? 'Boa tarde' : 'Boa noite');

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
        $barber =$request->user();

        $workingHours = WorkingHour::where('user_id',$barber->id)
            ->orderBy('day_of_week')
            ->get()
            ->keyBy('day_of_week');

        $blocks = ScheduleBlock::where('user_id',$barber->id)
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
        $barber =$request->user();

        $validated =$request->validate([
            'days'               => ['required', 'array'],
            'days.*.active'      => ['nullable', 'boolean'],
            'days.*.start_time'  => ['nullable', 'date_format:H:i'],
            'days.*.end_time'    => ['nullable', 'date_format:H:i'],
        ]);

        foreach ($validated['days'] as $dayOfWeek =>$data) {
            $day = (int)$dayOfWeek;
            $isActive = !empty($data['active']) && !empty($data['start_time']) && !empty($data['end_time']);

            if ($isActive && $data['end_time'] >$data['start_time']) {
                WorkingHour::updateOrCreate(
                    ['user_id' => $barber->id, 'day_of_week' =>$day],
                    ['start_time' => $data['start_time'], 'end_time' =>$data['end_time']]
                );
            } else {
                WorkingHour::where('user_id', $barber->id)
                    ->where('day_of_week', $day)
                    ->delete();
            }
        }

        return redirect()->route('barber.availability')
            ->with('success', 'Horário semanal atualizado com sucesso.');
    }

    public function storeBlock(Request $request)
    {
        $barber =$request->user();
        $isAllDay =$request->boolean('all_day');

        $validated =$request->validate([
            'date'       => ['required', 'date', 'after_or_equal:today'],
            'all_day'    => ['nullable', 'boolean'],
            'start_time' => [$isAllDay ? 'nullable' : 'required', 'date_format:H:i'],
            'end_time'   => [$isAllDay ? 'nullable' : 'required', 'date_format:H:i', 'after:start_time'],
            'reason'     => ['nullable', 'string', 'max:140'],
        ]);

        ScheduleBlock::create([
            'user_id'    => $barber->id,
            'date'       => $validated['date'],
            'start_time' => $isAllDay ? '00:00:00' : $validated['start_time'],
            'end_time'   => $isAllDay ? '23:59:59' : $validated['end_time'],
            'reason'     => $validated['reason'] ?? 'Indisponibilidade',
        ]);

        return redirect()->route('barber.availability')
            ->with('success', 'Bloqueio registado na agenda.');
    }

    public function destroyBlock(Request $request, ScheduleBlock$block)
    {
        if ((int) $block->user_id !== (int)$request->user()->id) {
            abort(403, 'Não tens permissão para remover este bloqueio.');
        }

        $block->delete();

        return redirect()->route('barber.availability')
            ->with('success', 'Bloqueio removido.');
    }

    public function services(Request $request)
    {
        $barber =$request->user();
        $services =$barber->services()->orderBy('category')->orderBy('name')->get();

        return view('barber.services', compact('barber', 'services'));
    }

    public function reviews(Request $request)
    {
        $barber =$request->user();

        // Suporta o schema polimórfico atual e fica preparado para relação com Appointment
        $reviews = Review::with(['user', 'reviewable'])
            ->where(function ($query) use ($barber) {
                $query->where(function ($q) use ($barber) {$q->where('reviewable_type', \App\Models\User::class)
                      ->where('reviewable_id', $barber->id);
                })->orWhere(function ($q) use ($barber) {$q->where('reviewable_type', Appointment::class)
                      ->whereIn('reviewable_id', Appointment::where('barber_id', $barber->id)->select('id'));
                });
            })
            ->latest()
            ->get();

        $totalReviews = $reviews->count();$averageRating = $totalReviews > 0 ? round($reviews->avg('rating'), 1) : 0;

        return view('barber.reviews', compact('barber', 'reviews', 'totalReviews', 'averageRating'));
    }
}