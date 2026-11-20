<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarMarcacaoRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Marcações via API. As regras de acesso são as mesmas da parte web
 * (AppointmentPolicy): cliente vê as suas, barbeiro as que tem a cargo,
 * admin vê todas.
 */
class AppointmentController extends Controller
{
    // Filtros opcionais: ?estado=pending|confirmed|cancelled|completed
    public function index(Request $request)
    {
        $this->authorize('viewAny', Appointment::class);

        $appointments = Appointment::with(['customer', 'barber', 'service'])
            ->visibleTo($request->user())
            ->withStatus(Status::tryFrom($request->string('estado')->toString()))
            ->orderByDesc('date')
            ->orderBy('time')
            ->paginate(15)
            ->withQueryString();

        return AppointmentResource::collection($appointments);
    }

    public function show(Appointment $appointment): AppointmentResource
    {
        $this->authorize('view', $appointment);

        return new AppointmentResource($appointment->load(['customer', 'barber', 'service']));
    }

    public function store(GuardarMarcacaoRequest $request): JsonResponse
    {
        $data = $request->validated();

        $barber = User::findOrFail($data['barber_id']);
        $service = Service::findOrFail($data['service_id']);

        $this->ensureBarberIsAvailable($barber, $service, $data['date'], $data['time']);

        $appointment = Appointment::create([
            'date' => $data['date'],
            'time' => $data['time'],
            'customer_id' => $request->user()->isAdmin() ? $data['customer_id'] : $request->user()->id,
            'service_id' => $service->id,
            'barber_id' => $barber->id,
        ]);

        return (new AppointmentResource($appointment->load(['customer', 'barber', 'service'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(GuardarMarcacaoRequest $request, Appointment $appointment): AppointmentResource
    {
        $data = $request->validated();

        $barber = User::findOrFail($data['barber_id']);
        $service = Service::findOrFail($data['service_id']);

        $this->ensureBarberIsAvailable($barber, $service, $data['date'], $data['time'], $appointment->id);

        $appointment->update([
            'date' => $data['date'],
            'time' => $data['time'],
            'service_id' => $service->id,
            'barber_id' => $barber->id,
        ]);

        return new AppointmentResource($appointment->load(['customer', 'barber', 'service']));
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $this->authorize('delete', $appointment);

        $appointment->delete();

        return response()->json(null, 204);
    }

    private function ensureBarberIsAvailable(User $barber, Service $service, string $date, string $time, ?int $ignoreId = null): void
    {
        if (! $barber->isAvailableFor($service, $date, $time, $ignoreId)) {
            throw ValidationException::withMessages([
                'time' => 'O barbeiro não está disponível neste horário para este serviço.',
            ]);
        }
    }
}
