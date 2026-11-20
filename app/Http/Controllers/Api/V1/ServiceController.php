<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarServicoRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

/**
 * Serviços da barbearia.
 * Leitura: qualquer cliente autenticado. Escrita: só admin (ServicePolicy).
 */
class ServiceController extends Controller
{
    public function index()
    {
        return ServiceResource::collection(Service::orderBy('name')->paginate(15));
    }

    public function show(Service $service): ServiceResource
    {
        return new ServiceResource($service);
    }

    public function store(GuardarServicoRequest $request): JsonResponse
    {
        $service = Service::create($request->validated());

        return (new ServiceResource($service))->response()->setStatusCode(201);
    }

    public function update(GuardarServicoRequest $request, Service $service): ServiceResource
    {
        $service->update($request->validated());

        return new ServiceResource($service);
    }

    public function destroy(Service $service): JsonResponse
    {
        $this->authorize('delete', $service);

        $service->delete();

        return response()->json(null, 204);
    }
}
