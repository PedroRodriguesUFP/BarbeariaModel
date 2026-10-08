<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;        
use App\Models\Appointment;

class AppointmentController extends Controller
{
    public function index()
    {
        $appointments = Appointment::all();

        return view('appointments.index', compact("appointments"));
        }

        public function create()
        {
            return view('appointments.create');
        }

        public function store(Request $request)
        {
            // valida se o formulario é válido
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


}

public function show($id)
{
    $appointment = Appointment::find($id);
    return view('appointments.show', compact('appointment'));
}
