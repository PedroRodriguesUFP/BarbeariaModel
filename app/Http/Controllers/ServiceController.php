<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    // Lista todos os serviços.
    public function index()
    {
        $services = Service::all();

    return view('services.index', compact('services'));
    }

    // Apresenta o formulário de criação.
    public function create()
    {
        return view('services.create');
    }

    // Valida e guarda um novo serviço.
    public function store(Request $request)
    {
        $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'duration_minutes' => 'required|integer|min:1',
        'category' => 'required|string',
    ]);

    Service::create($validated);

    return redirect()
        ->route('services.index')
        ->with('success', 'Service created successfully.');
    }

    // Apresenta um serviço específico.
    public function show(Service $service)
    {
         return view('services.show', compact('service'));
    }

    // Apresenta o formulário de edição.
    public function edit(Service $service)
    { 
         return view('services.edit', compact('service'));
    }

    // Valida e atualiza um serviço.
    public function update(Request $request, Service $service)
    {
         $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'duration_minutes' => 'required|integer|min:1',
        'category' => 'required|string',
    ]);

    $service->update($validated);

    return redirect()
        ->route('services.index')
        ->with('success', 'Service updated successfully.');
    }

    // Apaga um serviço.
    public function destroy(Service $service)
    {
         $service->delete();

    return redirect()
        ->route('services.index')
        ->with('success', 'Service deleted successfully.');
    }
}
