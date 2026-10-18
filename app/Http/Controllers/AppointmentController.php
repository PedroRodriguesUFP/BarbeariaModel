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
    // Lista todas as marcações.
    public function index()
    {
        $appointments = Appointment::with([
            'customer',
            'barber',
            'service',
        ])->get();

        return view(
            'appointments.index',
            compact('appointments')
        );
    }

    // Apresenta o formulário de criação.
    public function create()
    {
        return view('appointments.create');
    }

    // Cria uma nova marcação.
    public function store(Request $request)
    {
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

        /*
         * Verifica:
         *
         * - se é barbeiro;
         * - se realiza o serviço;
         * - se trabalha nesse horário;
         * - se existem bloqueios;
         * - se já tem outra marcação.
         */
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

    // Mostra uma marcação.
    public function show($id)
    {
        $appointment = Appointment::with([
            'customer',
            'barber',
            'service',
        ])->findOrFail($id);

        return view(
            'appointments.show',
            compact('appointment')
        );
    }

    // Formulário de edição.
    public function edit($id)
    {
        $appointment = Appointment::findOrFail($id);

        return view(
            'appointments.edit',
            compact('appointment')
        );
    }

    // Atualiza uma marcação.
    public function update(Request $request, $id)
    {
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

        $appointment = Appointment::findOrFail($id);

        $barber = User::findOrFail(
            $request->input('barber_id')
        );

        $service = Service::findOrFail(
            $request->input('service_id')
        );

        /*
         * Passamos o ID da própria marcação para ela
         * não entrar em conflito consigo mesma.
         */
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

    // Elimina uma marcação.
    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);

        $appointment->delete();

        return redirect()
            ->route('appointments.index')
            ->with(
                'success',
                'Marcação eliminada com sucesso.'
            );
    }
}