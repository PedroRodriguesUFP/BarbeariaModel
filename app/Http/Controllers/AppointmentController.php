<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Enums\UserRole;
use App\Http\Requests\GuardarMarcacaoRequest;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Marcações da barbearia.
 *
 * Quem vê o quê:
 *  - Cliente: só as suas marcações.
 *  - Barbeiro: só as marcações que tem a seu cargo.
 *  - Admin: todas.
 *
 * Quem muda o estado:
 *  - Confirmar e concluir: o barbeiro da marcação ou o admin.
 *  - Cancelar: também o cliente que fez a marcação.
 *
 * As permissões finas (quem pode criar, editar, apagar) estão nas Policies,
 * aqui só chamamos $this->authorize(...).
 */
class AppointmentController extends Controller
{
    // ------------------------------------------------------------------
    // Listagem e detalhe
    // ------------------------------------------------------------------

    // Parâmetros aceites (todos opcionais):
    //   ?q=texto       pesquisa por cliente, barbeiro ou serviço
    //   ?estado=...    só marcações com esse estado
    //   ?barbeiro=ID   só marcações desse barbeiro
    //   ?ordem=...     "data" (por defeito) ou "estado"
    public function index(Request $request)
    {
        $this->authorize('viewAny', Appointment::class);

        // A ordenação entra no SQL, por isso só aceitamos valores de uma lista branca.
        $ordem = $request->string('ordem')->toString();
        $ordem = in_array($ordem, ['data', 'estado'], true) ? $ordem : 'data';

        // O estado também só vale se for um estado que existe.
        $estado = Status::tryFrom($request->string('estado')->toString());

        // Carregamos já as relações para a vista não fazer uma consulta por linha.
        $query = Appointment::with(['customer', 'barber', 'service', 'payments'])
            ->visibleTo($request->user())
            ->search($request->string('q')->toString())
            ->withStatus($estado)
            ->when(
                $request->filled('barbeiro'),
                fn ($q) => $q->where('barber_id', $request->integer('barbeiro'))
            );

        if ($ordem === 'estado') {
            $query->orderBy('status');
        }

        $appointments = $query
            ->orderByDesc('date')
            ->orderBy('time')
            ->paginate(15)
            ->withQueryString(); // mantém os filtros ao mudar de página

        $barbers = User::where('role', UserRole::Barber->value)->orderBy('name')->get(['id', 'name']);
        $statuses = Status::cases();

        return view('appointments.index', compact('appointments', 'barbers', 'statuses'));
    }

    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);

        $appointment->load(['customer', 'barber', 'service', 'payments']);

        return view('appointments.show', compact('appointment'));
    }

    // ------------------------------------------------------------------
    // Criar
    // ------------------------------------------------------------------

    public function create()
    {
        $this->authorize('create', Appointment::class);

        return view('appointments.create', $this->formData());
    }

    // A validação e a autorização estão em GuardarMarcacaoRequest.
    public function store(GuardarMarcacaoRequest $request)
    {
        $data = $request->validated();

        $barber = User::findOrFail($data['barber_id']);
        $service = Service::findOrFail($data['service_id']);

        $this->ensureBarberIsAvailable($barber, $service, $data['date'], $data['time']);

        // O cliente marca para si próprio.
        // O admin escolheu o cliente no formulário (já validado no request).
        $customerId = $request->user()->isAdmin()
            ? $data['customer_id']
            : $request->user()->id;

        Appointment::create([
            'date' => $data['date'],
            'time' => $data['time'],
            'customer_id' => $customerId,
            'service_id' => $service->id,
            'barber_id' => $barber->id,
        ]);

        return redirect()
            ->route('appointments.index')
            ->with('success', 'Marcação criada com sucesso.');
    }

    // ------------------------------------------------------------------
    // Editar
    // ------------------------------------------------------------------

    public function edit(Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        return view(
            'appointments.edit',
            $this->formData() + compact('appointment')
        );
    }

    public function update(GuardarMarcacaoRequest $request, Appointment $appointment)
    {
        $data = $request->validated();

        $barber = User::findOrFail($data['barber_id']);
        $service = Service::findOrFail($data['service_id']);

        // Passamos o id desta marcação para ela não "chocar" consigo própria
        // quando o horário fica igual.
        $this->ensureBarberIsAvailable(
            $barber,
            $service,
            $data['date'],
            $data['time'],
            $appointment->id
        );

        // O cliente não muda numa edição, só data, hora, serviço e barbeiro.
        $appointment->update([
            'date' => $data['date'],
            'time' => $data['time'],
            'service_id' => $service->id,
            'barber_id' => $barber->id,
        ]);

        return redirect()
            ->route('appointments.index')
            ->with('success', 'Marcação atualizada com sucesso.');
    }

    // ------------------------------------------------------------------
    // Apagar
    // ------------------------------------------------------------------

    public function destroy(Appointment $appointment)
    {
        $this->authorize('delete', $appointment);

        $appointment->delete();

        return redirect()
            ->route('appointments.index')
            ->with('success', 'Marcação eliminada com sucesso.');
    }

    // ------------------------------------------------------------------
    // Mudanças de estado: Pendente -> Confirmada -> Concluída
    //                     (Pendente ou Confirmada -> Cancelada)
    // Quem pode fazer cada uma está na AppointmentPolicy.
    // ------------------------------------------------------------------

    public function confirm(Appointment $appointment)
    {
        $this->authorize('confirm', $appointment);

        // O admin passa sempre na policy, por isso o estado vê-se aqui também.
        if (! $appointment->canBeConfirmed()) {
            return back()->withErrors([
                'status' => 'Apenas marcações pendentes podem ser confirmadas.',
            ]);
        }

        $appointment->confirmAppointment();

        return back()->with('success', 'Marcação confirmada.');
    }

    public function complete(Appointment $appointment)
    {
        $this->authorize('complete', $appointment);

        if (! $appointment->canBeCompleted()) {
            return back()->withErrors([
                'status' => 'Apenas marcações confirmadas podem ser concluídas.',
            ]);
        }

        $appointment->completeAppointment();

        return back()->with('success', 'Marcação concluída.');
    }

    public function cancel(Appointment $appointment)
    {
        $this->authorize('cancel', $appointment);

        if (! $appointment->canBeCancelled()) {
            return back()->withErrors([
                'status' => 'Esta marcação já não pode ser cancelada.',
            ]);
        }

        $appointment->cancelAppointment();

        return back()->with('success', 'Marcação cancelada.');
    }

    // ------------------------------------------------------------------
    // Auxiliares privados
    // ------------------------------------------------------------------

    /**
     * Listas para os <select> dos formulários de criar e editar.
     * Só aparecem barbeiros e clientes ativos.
     */
    private function formData(): array
    {
        return [
            'barbers' => User::where('role', UserRole::Barber->value)
                ->where('active', true)
                ->with('services')
                ->orderBy('name')
                ->get(),

            'services' => Service::orderBy('name')->get(),

            'customers' => User::where('role', UserRole::Client->value)
                ->where('active', true)
                ->orderBy('name')
                ->get(),
        ];
    }

    /**
     * Pára com um erro no campo "time" se o barbeiro não puder
     * fazer este serviço a esta hora.
     *
     * $ignoreAppointmentId serve para edições: a própria marcação
     * não conta como conflito.
     */
    private function ensureBarberIsAvailable(
        User $barber,
        Service $service,
        string $date,
        string $time,
        ?int $ignoreAppointmentId = null
    ): void {
        $isAvailable = $barber->isAvailableFor(
            $service,
            $date,
            $time,
            $ignoreAppointmentId
        );

        if (! $isAvailable) {
            throw ValidationException::withMessages([
                'time' => 'O barbeiro não está disponível neste horário para este serviço.',
            ]);
        }
    }
}
