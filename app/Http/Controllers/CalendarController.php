<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CalendarController extends Controller
{
    public function mine(Request $request)
    {
        return view(
            'calendar.show',
            $this->buildCalendarData($request->user(), $request->query('month'))
        );
    }

    public function show(Request $request, User $barber)
    {
        abort_unless($barber->role === UserRole::Barber, 404);

        $this->authorize('viewBarberCalendar', $barber);

        return view(
            'calendar.show',
            $this->buildCalendarData($barber, $request->query('month'), readOnly: true)
        );
    }

    public function barbers()
    {
        $barbers = User::where('role', UserRole::Barber)
            ->orderBy('name')
            ->get();

        return view('barbers.index', compact('barbers'));
    }

    public function create()
    {
        $this->authorize('createWalkIn', Appointment::class);

        $clients = User::where('role', UserRole::Client)
            ->orderBy('name')
            ->get();

        return view('calendar.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $this->authorize('createWalkIn', Appointment::class);

        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'service_id' => ['required', 'exists:services,id'],
            'client_type' => ['required', Rule::in(['existing', 'walk_in'])],
            'customer_id' => [
                'required_if:client_type,existing',
                'nullable',
                Rule::exists('users', 'id')->where('role', UserRole::Client->value),
            ],
            'walk_in_name' => ['required_if:client_type,walk_in', 'nullable', 'string', 'max:255'],
            'walk_in_phone' => ['nullable', 'string', 'max:30'],
        ]);

        $barber = $request->user();
        $service = Service::findOrFail($data['service_id']);

        if (! $barber->isAvailableFor($service, $data['date'], $data['time'])) {
            throw ValidationException::withMessages([
                'time' => 'Já tens outra marcação, bloqueio ou indisponibilidade nesse horário.',
            ]);
        }

        Appointment::create([
            'date' => $data['date'],
            'time' => $data['time'],
            'service_id' => $service->id,
            'barber_id' => $barber->id,
            'customer_id' => $data['client_type'] === 'existing' ? $data['customer_id'] : null,
            'walk_in_name' => $data['client_type'] === 'walk_in' ? $data['walk_in_name'] : null,
            'walk_in_phone' => $data['client_type'] === 'walk_in' ? ($data['walk_in_phone'] ?? null) : null,
        ]);

        return redirect()
            ->route('calendar.mine')
            ->with('success', 'Marcação adicionada à agenda.');
    }

    private function buildCalendarData(User $barber, ?string $monthParam, bool $readOnly = false): array
    {
        $month = ($monthParam && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthParam))
            ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
            : now()->startOfMonth();

        $gridStart = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::MONDAY);

        $appointments = Appointment::with(['customer', 'service'])
            ->where('barber_id', $barber->id)
            ->whereBetween('date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->orderBy('time')
            ->get()
            ->groupBy(fn (Appointment $appointment) => $appointment->date->format('Y-m-d'));

        $weeks = [];
        $cursor = $gridStart->copy();

        while ($cursor <= $gridEnd) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $week[] = [
                    'date' => $cursor->copy(),
                    'inMonth' => $cursor->month === $month->month,
                    'isToday' => $cursor->isToday(),
                    'appointments' => $appointments->get($cursor->format('Y-m-d'), collect()),
                ];

                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return [
            'barber' => $barber,
            'month' => $month,
            'weeks' => $weeks,
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'readOnly' => $readOnly,
        ];
    }
}