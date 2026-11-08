<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function index(Request $request)
    {
        $this->authorize('viewAny', Appointment::class);

        $user = $request->user();

        // Carregamos já as relações para a view não fazer uma query por linha.
        $query = Appointment::with(['customer', 'barber', 'service', 'payments']);

        // Cliente e barbeiro só veem as suas. O admin não leva filtro.
        if ($user->role === UserRole::Client) {
            $query->where('customer_id', $user->id);
        } elseif ($user->role === UserRole::Barber) {
            $query->where('barber_id', $user->id);
        }

        // 15 por página, as mais recentes primeiro.
        // Na view, usar {{ $appointments->links() }} para os botões de página.
        $appointments = $query
            ->orderByDesc('date')
            ->orderBy('time')
            ->paginate(15);

        return view('appointments.index', compact('appointments'));
    }

    public function show($id)
    {
        $appointment = Appointment::with(['customer', 'barber', 'service', 'payments'])
            ->findOrFail($id);

        $this->authorize('view', $appointment);

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

    public function store(Request $request)
    {
        $this->authorize('create', Appointment::class);

        $data = $this->validateAppointment($request);

        $barber  = User::findOrFail($data['barber_id']);
        $service = Service::findOrFail($data['service_id']);

        $this->ensureBarberIsAvailable($barber, $service, $data['date'], $data['time']);

        // O cliente marca para si próprio.
        // O admin tem de escolher para quem está a marcar.
        $customerId = $request->user()->id;

        if ($request->user()->isAdmin()) {
            $customerId = $this->validateCustomerId($request);
        }

        Appointment::create([
            'date'        => $data['date'],
            'time'        => $data['time'],
            'customer_id' => $customerId,
            'service_id'  => $service->id,
            'barber_id'   => $barber->id,
        ]);

        return redirect()
            ->route('appointments.index')
            ->with('success', 'Marcação criada com sucesso.');
    }

    // ------------------------------------------------------------------
    // Editar
    // ------------------------------------------------------------------

    public function edit($id)
    {
        $appointment = Appointment::findOrFail($id);

        $this->authorize('update', $appointment);

        return view(
            'appointments.edit',
            $this->formData() + compact('appointment')
        );
    }

    public function update(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $this->authorize('update', $appointment);

        $data = $this->validateAppointment($request);

        $barber  = User::findOrFail($data['barber_id']);
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
            'date'       => $data['date'],
            'time'       => $data['time'],
            'service_id' => $service->id,
            'barber_id'  => $barber->id,
        ]);

        return redirect()
            ->route('appointments.index')
            ->with('success', 'Marcação atualizada com sucesso.');
    }

    // ------------------------------------------------------------------
    // Apagar
    // ------------------------------------------------------------------

    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);

        $this->authorize('delete', $appointment);

        $appointment->delete();

        return redirect()
            ->route('appointments.index')
            ->with('success', 'Marcação eliminada com sucesso.');
    }

    // ------------------------------------------------------------------
    // Mudanças de estado: Pendente -> Confirmada -> Concluída
    //                     (Pendente ou Confirmada -> Cancelada)
    // Só o barbeiro da marcação ou um admin pode confirmar e concluir.
    // Cancelar também é permitido ao cliente da marcação.
    // ------------------------------------------------------------------

    public function confirm(Request $request, Appointment $appointment)
    {
        $this->authorizeBarberOrAdmin($request, $appointment);

        if (! $appointment->canBeConfirmed()) {
            return back()->withErrors([
                'status' => 'Apenas marcações pendentes podem ser confirmadas.',
            ]);
        }

        $appointment->confirmAppointment();

        return back()->with('success', 'Marcação confirmada.');
    }

    public function complete(Request $request, Appointment $appointment)
    {
        $this->authorizeBarberOrAdmin($request, $appointment);

        if (! $appointment->canBeCompleted()) {
            return back()->withErrors([
                'status' => 'Apenas marcações confirmadas podem ser concluídas.',
            ]);
        }

        $appointment->completeAppointment();

        return back()->with('success', 'Marcação concluída.');
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        // Aqui o próprio cliente também pode cancelar a sua marcação.
        $this->authorizeBarberOrAdmin($request, $appointment, allowCustomer: true);

        // Só se cancela o que ainda está em aberto.
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
     * Regras de validação iguais em criar e editar.
     * Devolve só os campos já validados.
     */
    private function validateAppointment(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],

            // O utilizador escolhido tem mesmo de ser um barbeiro.
            'barber_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', UserRole::Barber->value),
            ],

            'service_id' => ['required', 'exists:services,id'],
        ]);
    }

    /**
     * Validação do cliente escolhido pelo admin.
     * Tem de existir e ter o papel de cliente.
     */
    private function validateCustomerId(Request $request): int
    {
        $validated = $request->validate([
            'customer_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', UserRole::Client->value),
            ],
        ]);

        return $validated['customer_id'];
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

    /**
     * Só passam daqui o barbeiro da marcação e os admins.
     * Com $allowCustomer = true, o cliente da marcação também passa.
     * Os outros levam 403.
     */
    private function authorizeBarberOrAdmin(
        Request $request,
        Appointment $appointment,
        bool $allowCustomer = false
    ): void {
        $user = $request->user();

        $isTheBarber   = (int) $appointment->barber_id === (int) $user->id;
        $isTheCustomer = $appointment->customer_id !== null
            && (int) $appointment->customer_id === (int) $user->id;

        if ($user->isAdmin() || $isTheBarber) {
            return;
        }

        if ($allowCustomer && $isTheCustomer) {
            return;
        }

        abort(403);
    }
}
