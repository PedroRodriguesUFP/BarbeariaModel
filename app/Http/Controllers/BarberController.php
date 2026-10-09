<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Barber;

class BarberController extends Controller
{
    public function index()
    {
        $barbers = Barber::with('user')->get();

        return view('barbers.index', compact('barbers'));
    }

    public function create()
    {
        return view('barbers.create');
    }

    public function store(Request $request)
    {
        // valida se o formulario é válido
        $request->validate([
            'user_id' => 'required|exists:users,id|unique:barbers,user_id',
            'phone' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
        ]);

        Barber::create([
            'user_id' => $request->input('user_id'),
            'phone' => $request->input('phone'),
            'bio' => $request->input('bio'),
        ]);

        return redirect()->route('barbers.index')->with('success', 'Barber created successfully.');
    }

    public function show($id)
    {
        $barber = Barber::with('user')->find($id);

        if (! $barber) {
            throw new \Exception('Barber not found, please check if the Barber exists and try again.');
        }

        return view('barbers.show', compact('barber'));
    }

    public function edit($id)
    {
        $barber = Barber::find($id);

        if (! $barber) {
            throw new \Exception('Barber not found, please check if the Barber exists and try again.');
        }

        return view('barbers.edit', compact('barber'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('barbers', 'user_id')->ignore($id),
            ],
            'phone' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
        ]);

        $barber = Barber::find($id);

        if ($barber) {
            $barber->update([
                'user_id' => $request->input('user_id'),
                'phone' => $request->input('phone'),
                'bio' => $request->input('bio'),
            ]);

            return redirect()->route('barbers.index')->with('success', 'Barber updated successfully.');
        } else {
            throw new \Exception('Barber not found, please check if the Barber exists and try again.');
        }
    }

    public function destroy($id)
    {
        $barber = Barber::find($id);

        if ($barber) {
            $barber->delete();

            return redirect()->route('barbers.index')->with('success', 'Barber deleted successfully.');
        } else {
            throw new \Exception('Barber not found, please check if the Barber exists and try again.');
        }
    }
}