<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Appointment::class);

        $query = Appointment::with([
            'customer',
            'barber',
            'service',
        ]);

        $user = $request->user();

        if ($user->role === UserRole::Client) {
            $query->where('customer_id', $user->id);
        } elseif ($user->role === UserRole::Barber) {
            $query->where('barber_id', $user->id);
        }

        $appointments = $query->get();

        return view(
            'appointments.index',
            compact('appointments')
        );
    }

    public function create()
    {
        $this->authorize('create', Appointment::class);

        return view('appointments.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Appointment::class);

        $request->validate([
            'date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],

            'time' => [
                'required',
                'date_format:H:i',
            ],

            'barber_id' => [
                'required',
                Rule::exists('users', 'id')->where(
                    'role',
                    UserRole::Barber->value
                ),
            ],

            'service_id' => [
                'required',
                'exists:services,id',
            ],
        ]);

        $barber = User::findOrFail(
            $request->input('barber_id')
        );

        $service = Service::findOrFail(
            $request->input('service_id')
        );

        if (! $barber->isAvailableFor(
            $service,
            $request->input('date'),
            $request->input('time')
        )) {
            throw ValidationException::withMessages([
                'time' =>
                    'O barbeiro não está disponível neste horário para este serviço.',
            ]);
        }

        Appointment::create([
            'date' => $request->input('date'),
            'time' => $request->input('time'),
            'customer_id' => $request->user()->id,
            'service_id' => $service->id,
            'barber_id' => $barber->id,
        ]);

        return redirect()
            ->route('appointments.index')
            ->with(
                'success',
                'Marcação criada com sucesso.'
            );
    }

    public function show($id)
    {
        $appointment = Appointment::with([
            'customer',
            'barber',
            'service',
        ])->findOrFail($id);

        $this->authorize('view', $appointment);

        return view(
            'appointments.show',
            compact('appointment')
        );
    }

    public function edit($id)
    {
        $appointment = Appointment::findOrFail($id);

        $this->authorize('update', $appointment);

        return view(
            'appointments.edit',
            compact('appointment')
        );
    }

    public function update(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $this->authorize('update', $appointment);

        $request->validate([
            'date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],

            'time' => [
                'required',
                'date_format:H:i',
            ],

            'barber_id' => [
                'required',
                Rule::exists('users', 'id')->where(
                    'role',
                    UserRole::Barber->value
                ),
            ],

            'service_id' => [
                'required',
                'exists:services,id',
            ],
        ]);

        $barber = User::findOrFail(
            $request->input('barber_id')
        );

        $service = Service::findOrFail(
            $request->input('service_id')
        );

        if (! $barber->isAvailableFor(
            $service,
            $request->input('date'),
            $request->input('time'),
            $appointment->id
        )) {
            throw ValidationException::withMessages([
                'time' =>
                    'O barbeiro não está disponível neste horário para este serviço.',
            ]);
        }

        $appointment->update([
            'date' => $request->input('date'),
            'time' => $request->input('time'),
            'service_id' => $service->id,
            'barber_id' => $barber->id,
        ]);

        return redirect()
            ->route('appointments.index')
            ->with(
                'success',
                'Marcação atualizada com sucesso.'
            );
    }

    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);

        $this->authorize('delete', $appointment);

        $appointment->delete();

        return redirect()
            ->route('appointments.index')
            ->with(
                'success',
                'Marcação eliminada com sucesso.'
            );
    }
    // Dentro de AppointmentController.php:

 public function confirm(Request $request, Appointment$appointment)
 {
    $user =$request->user();

    if ((int) $appointment->barber_id !== (int) $user->id && !$user->isAdmin()) {
        abort(403);
    }

    if ($appointment->status !== \App\Enums\Status::Pending) {
        return back()->withErrors(['status' => 'Apenas marcações pendentes podem ser confirmadas.']);
    }

    $appointment->confirmAppointment();

    return back()->with('success', 'Marcação confirmada.');
}

public function complete(Request $request, Appointment$appointment)
{
    $user =$request->user();

    if ((int) $appointment->barber_id !== (int) $user->id && !$user->isAdmin()) {
        abort(403);
    }

    if ($appointment->status !== \App\Enums\Status::Confirmed) {
        return back()->withErrors(['status' => 'Apenas marcações confirmadas podem ser concluídas.']);
    }

    $appointment->completeAppointment();

    return back()->with('success', 'Marcação concluída.');
}

public function cancel(Request $request, Appointment$appointment)
{
    $user =$request->user();

    if ((int) $appointment->barber_id !== (int) $user->id && !$user->isAdmin()) {
        abort(403);
    }

    if (!in_array($appointment->status, [\App\Enums\Status::Pending, \App\Enums\Status::Confirmed], true)) {
        return back()->withErrors(['status' => 'Esta marcação já não pode ser cancelada.']);
    }

    $appointment->cancelAppointment();

    return back()->with('success', 'Marcação cancelada.');
    }
}