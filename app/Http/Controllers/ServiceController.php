<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarServicoRequest;
use App\Models\Service;

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
    // Validação e autorização: GuardarServicoRequest.
    public function store(GuardarServicoRequest $request)
    {
        $validated = $request->validated();

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
    public function update(GuardarServicoRequest $request, Service $service)
    {
         $validated = $request->validated();

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
