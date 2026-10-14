<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        return view('appointments.index', compact('appointments'));
    }

    // Apresenta o formulário de criação.
    public function create()
    {
        return view('appointments.create');
    }

    // Valida e guarda uma nova marcação.
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',

            'customer_id' => [
                'required',
                'exists:users,id',
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

        Appointment::create([
            'date' => $request->input('date'),
            'time' => $request->input('time'),
            'customer_id' => $request->input('customer_id'),
            'service_id' => $request->input('service_id'),
            'barber_id' => $request->input('barber_id'),
        ]);

        return redirect()
            ->route('appointments.index')
            ->with('success', 'Appointment created successfully.');
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
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',

            'customer_id' => [
                'required',
                'exists:users,id',
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

        $appointment->update([
            'date' => $request->input('date'),
            'time' => $request->input('time'),
            'customer_id' => $request->input('customer_id'),
            'service_id' => $request->input('service_id'),
            'barber_id' => $request->input('barber_id'),
        ]);

        return redirect()
            ->route('appointments.index')
            ->with('success', 'Appointment updated successfully.');
    }

    // Elimina uma marcação.
    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);

        $appointment->delete();

        return redirect()
            ->route('appointments.index')
            ->with('success', 'Appointment deleted successfully.');
    }
}