<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class BarberController extends Controller
{
    public function index()
    {
        $barbers = User::where('role', UserRole::Barber->value)
            ->withCount('appointmentsAsBarber', 'services')
            ->orderBy('name')
            ->get();

        return view('admin.barbers.index', compact('barbers'));
    }

    public function create()
    {
        return view('admin.barbers.create', [
            'services' => Service::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'services' => ['nullable', 'array'],
            'services.*' => ['exists:services,id'],
            'default_hours' => ['nullable', 'boolean'],
        ]);

        $barber = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'bio' => $data['bio'] ?? null,
            'password' => $data['password'],
            'role' => UserRole::Barber->value,
            'active' => true,
        ]);

        $barber->services()->sync($data['services'] ?? []);

        // Sem horário o barbeiro nunca aparece disponível.
        if ($request->boolean('default_hours')) {
            $this->createDefaultHours($barber);
        }

        return redirect()
            ->route('admin.barbers.index')
            ->with('success', 'Barbeiro criado com sucesso.');
    }

    public function edit(User $barber)
    {
        $this->ensureBarber($barber);

        return view('admin.barbers.edit', [
            'barber' => $barber->load('services'),
            'services' => Service::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $barber)
    {
        $this->ensureBarber($barber);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($barber->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'services' => ['nullable', 'array'],
            'services.*' => ['exists:services,id'],
            'active' => ['nullable', 'boolean'],
        ]);

        $barber->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'bio' => $data['bio'] ?? null,
            'active' => $request->boolean('active'),
        ]);

        if (! empty($data['password'])) {
            $barber->password = $data['password'];
        }

        $barber->save();
        $barber->services()->sync($data['services'] ?? []);

        return redirect()
            ->route('admin.barbers.index')
            ->with('success', 'Barbeiro atualizado.');
    }

    public function destroy(User $barber)
    {
        $this->ensureBarber($barber);

        // Apagar levava as marcações atrás (cascade). Desativar é mais seguro.
        if ($barber->appointmentsAsBarber()->exists()) {
            return back()->withErrors([
                'barber' => 'Este barbeiro tem marcações. Desativa a conta em vez de eliminar.',
            ]);
        }

        $barber->delete();

        return redirect()
            ->route('admin.barbers.index')
            ->with('success', 'Barbeiro eliminado.');
    }

    private function ensureBarber(User $user): void
    {
        abort_unless($user->role === UserRole::Barber, 404);
    }

    // Seg–Sex 09-13 / 14-18, Sáb 09-13 (igual ao WorkingHourSeeder).
    private function createDefaultHours(User $barber): void
    {
        for ($day = 1; $day <= 5; $day++) {
            $barber->workingHours()->create(['day_of_week' => $day, 'start_time' => '09:00', 'end_time' => '13:00']);
            $barber->workingHours()->create(['day_of_week' => $day, 'start_time' => '14:00', 'end_time' => '18:00']);
        }

        $barber->workingHours()->create(['day_of_week' => 6, 'start_time' => '09:00', 'end_time' => '13:00']);
    }
}
