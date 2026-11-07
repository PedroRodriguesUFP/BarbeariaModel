<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Models\Appointment;
use App\Models\ScheduleBlock;
use App\Models\WorkingHour;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Área do barbeiro: painel do dia, horário semanal, bloqueios na agenda,
 * serviços e avaliações.
 *
 * Todas as ações usam o barbeiro que tem a sessão iniciada ($request->user()),
 * por isso um barbeiro nunca vê nem altera dados de outro.
 */
class BarberController extends Controller
{
    // ------------------------------------------------------------------
    // Painel (dashboard)
    // ------------------------------------------------------------------

    public function dashboard(Request $request)
    {
        $barber = $request->user();
        $now    = Carbon::now();
        $today  = Carbon::today();

        // Tudo o que está marcado para hoje, por ordem de hora.
        $todayAppointments = Appointment::with(['customer', 'service'])
            ->where('barber_id', $barber->id)
            ->whereDate('date', $today)
            ->orderBy('time')
            ->get();

        // Marcações ainda ativas (pendentes ou confirmadas), de hoje para a frente.
        $activeAppointments = Appointment::with(['customer', 'service'])
            ->where('barber_id', $barber->id)
            ->whereDate('date', '>=', $today)
            ->whereIn('status', [Status::Pending, Status::Confirmed])
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        // Das ativas, tiramos as de hoje cuja hora já passou.
        $upcomingAppointments = [];

        foreach ($activeAppointments as $appointment) {
            if (! $this->hasAlreadyPassed($appointment, $now)) {
                $upcomingAppointments[] = $appointment;
            }
        }

        // A próxima é a primeira da lista (ou nenhuma, se a lista estiver vazia).
        $nextAppointment = $upcomingAppointments[0] ?? null;

        // Números que aparecem nos cartões do painel.
        $stats = [
            'today'     => $todayAppointments->count(),
            'upcoming'  => count($upcomingAppointments),
            'completed' => $todayAppointments->where('status', Status::Completed)->count(),
            'pending'   => $todayAppointments->where('status', Status::Pending)->count(),
        ];

        $greeting = $this->greetingFor($now);

        return view('barber.dashboard', compact(
            'barber',
            'greeting',
            'stats',
            'nextAppointment',
            'todayAppointments'
        ));
    }

    // ------------------------------------------------------------------
    // Disponibilidade: horário semanal e bloqueios
    // ------------------------------------------------------------------

    public function availability(Request $request)
    {
        $barber = $request->user();

        // Um registo por dia da semana, indexado pelo número do dia (0 a 6)
        // para a view encontrar o dia diretamente.
        $workingHours = WorkingHour::where('user_id', $barber->id)
            ->orderBy('day_of_week')
            ->get()
            ->keyBy('day_of_week');

        // Só interessam os bloqueios de hoje em diante.
        $blocks = ScheduleBlock::where('user_id', $barber->id)
            ->whereDate('date', '>=', Carbon::today())
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        // Começa na segunda-feira. O domingo é o dia 0 (convenção do Carbon).
        $daysOfWeek = [
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
            0 => 'Domingo',
        ];

        return view('barber.availability', compact(
            'barber',
            'workingHours',
            'blocks',
            'daysOfWeek'
        ));
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

            $isActive  = ! empty($dayData['active']);
            $startTime = $dayData['start_time'] ?? null;
            $endTime   = $dayData['end_time'] ?? null;

            // Horas válidas = as duas preenchidas e o fim depois do início.
            $hasValidTimes = $startTime && $endTime && $endTime > $startTime;

            if ($isActive && $hasValidTimes) {
                // Guarda o horário deste dia (cria se ainda não existir).
                WorkingHour::updateOrCreate(
                    ['user_id' => $barber->id, 'day_of_week' => $dayOfWeek],
                    ['start_time' => $startTime, 'end_time' => $endTime]
                );
            } else {
                // Dia desligado ou com horas inválidas:
                // o barbeiro não trabalha nesse dia, apagamos o registo.
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
        $barber   = $request->user();
        $isAllDay = $request->boolean('all_day');

        // Se o bloqueio é o dia todo, as horas não são precisas.
        $timeRule = $isAllDay ? 'nullable' : 'required';

        $validated = $request->validate([
            'date'       => ['required', 'date', 'after_or_equal:today'],
            'all_day'    => ['nullable', 'boolean'],
            'start_time' => [$timeRule, 'date_format:H:i'],
            'end_time'   => [$timeRule, 'date_format:H:i', 'after:start_time'],
            'reason'     => ['nullable', 'string', 'max:140'],
        ]);

        // Dia todo = das 00:00 às 23:59. Senão, usamos as horas escolhidas.
        if ($isAllDay) {
            $startTime = '00:00:00';
            $endTime   = '23:59:59';
        } else {
            $startTime = $validated['start_time'];
            $endTime   = $validated['end_time'];
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

        // Cada barbeiro só pode remover os seus bloqueios.
        // (int) para não falhar se um id vier como texto e o outro como número.
        if ((int) $block->user_id !== (int) $barber->id) {
            abort(403, 'Não tens permissão para remover este bloqueio.');
        }

        $block->delete();

        return redirect()->route('barber.availability')
            ->with('success', 'Bloqueio removido.');
    }

    // ------------------------------------------------------------------
    // Serviços e avaliações
    // ------------------------------------------------------------------

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

        // Só existem avaliações diretas ao barbeiro (reviewable_type = User).
        $reviews = $barber->reviewsReceived()
            ->with('reviewer')
            ->latest()
            ->get();

        $totalReviews = $reviews->count();

        // Sem avaliações a média é 0 (evita dividir por zero).
        $averageRating = 0;

        if ($totalReviews > 0) {
            $averageRating = round($reviews->avg('rating'), 1);
        }

        return view('barber.reviews', compact(
            'barber',
            'reviews',
            'totalReviews',
            'averageRating'
        ));
    }

    // ------------------------------------------------------------------
    // Auxiliares privados
    // ------------------------------------------------------------------

    /**
     * Uma marcação "já passou" se o início (data + hora) for anterior a agora.
     * Sem hora definida, nunca conta como passada.
     */
    private function hasAlreadyPassed(Appointment $appointment, Carbon $now): bool
    {
        if ($appointment->time === null) {
            return false;
        }

        // Junta a data com a hora (aceita "14:30" e "14:30:00").
        $start = $appointment->date->copy()->setTimeFromTimeString($appointment->time);

        return $start->lt($now);
    }

    /**
     * Bom dia até às 11:59, boa tarde até às 19:59, boa noite depois.
     */
    private function greetingFor(Carbon $now): string
    {
        $hour = (int) $now->format('H');

        if ($hour < 12) {
            return 'Bom dia';
        }

        if ($hour < 20) {
            return 'Boa tarde';
        }

        return 'Boa noite';
    }
}
