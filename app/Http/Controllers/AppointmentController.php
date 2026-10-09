<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index()
    {
        $appointments = Appointment::with([
            'client.user',
            'barber.user',
            'service',
        ])->get();

        return view('appointments.index', compact('appointments'));
    }

    public function create()
    {
        return view('appointments.create');
    }

    public function store(Request $request)
    {
        // valida se o formulário é válido
        $request->validate([
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'client_id' => 'required|exists:clients,id',
            'service_id' => 'required|exists:services,id',
            'barber_id' => 'required|exists:barbers,id',
        ]);

        Appointment::create([
            'date' => $request->input('date'),
            'time' => $request->input('time'),
            'client_id' => $request->input('client_id'),
            'service_id' => $request->input('service_id'),
            'barber_id' => $request->input('barber_id'),
        ]);

        return redirect()
            ->route('appointments.index')
            ->with('success', 'Appointment created successfully.');
    }

    public function show($id)
    {
        $appointment = Appointment::with([
            'client.user',
            'barber.user',
            'service',
        ])->find($id);

        if (! $appointment) {
            throw new \Exception(
                'Appointment not found, please check if the Appointment exists and try again.'
            );
        }

        return view('appointments.show', compact('appointment'));
    }

    public function edit($id)
    {
        $appointment = Appointment::find($id);

        if (! $appointment) {
            throw new \Exception(
                'Appointment not found, please check if the Appointment exists and try again.'
            );
        }

        return view('appointments.edit', compact('appointment'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'client_id' => 'required|exists:clients,id',
            'service_id' => 'required|exists:services,id',
            'barber_id' => 'required|exists:barbers,id',
        ]);

        $appointment = Appointment::find($id);

        if ($appointment) {
            $appointment->update([
                'date' => $request->input('date'),
                'time' => $request->input('time'),
                'client_id' => $request->input('client_id'),
                'service_id' => $request->input('service_id'),
                'barber_id' => $request->input('barber_id'),
            ]);

            return redirect()
                ->route('appointments.index')
                ->with('success', 'Appointment updated successfully.');
        } else {
            throw new \Exception(
                'Appointment not found, please check if the Appointment exists and try again.'
            );
        }
    }

    public function destroy($id)
    {
        $appointment = Appointment::find($id);

        if ($appointment) {
            $appointment->delete();

            return redirect()
                ->route('appointments.index')
                ->with('success', 'Appointment deleted successfully.');
        } else {
            throw new \Exception(
                'Appointment not found, please check if the Appointment exists and try again.'
            );
        }
    }
}