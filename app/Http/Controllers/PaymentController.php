<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Requests\GuardarPagamentoRequest;
use App\Models\Appointment;
use App\Models\Payment;
use App\Services\StripeGateway;
use Stripe\Exception\ApiConnectionException;
use App\Services\StripePaymentRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Pagamentos das marcações.
 *
 * Há duas formas de ter um pagamento:
 *  1. Registo manual (create/store/edit/update): formulário normal.
 *  2. Stripe (checkout/success/cancel): o cliente paga com cartão
 *     numa página da Stripe e volta ao site.
 *
 * Quem vê o quê: o admin vê todos, os outros só os seus (payer_id).
 *
 * Regra de segurança: só o admin define valor, estado e data de pagamento
 * à mão. Um cliente nunca escolhe quanto pagou nem se está "paid".
 */
class PaymentController extends Controller
{
    // ------------------------------------------------------------------
    // Listagem e detalhe
    // ------------------------------------------------------------------

    public function index()
    {
        $this->authorize('viewAny', Payment::class);

        $query = Payment::with([
            'payer',
            'appointment.barber',
            'appointment.service',
        ])->latest();

        // Quem não é admin só vê os pagamentos que fez.
        if (! auth()->user()->isAdmin()) {
            $query->where('payer_id', auth()->id());
        }

        // 15 por página. Na view, usar {{ $payments->links() }} para os botões.
        $payments = $query->paginate(15);

        return view('payments.index', compact('payments'));
    }

    public function show(Payment $payment)
    {
        $this->authorize('view', $payment);

        $payment->load([
            'payer',
            'appointment.barber',
            'appointment.service',
        ]);

        return view('payments.show', compact('payment'));
    }

    // ------------------------------------------------------------------
    // Criar (registo manual)
    // ------------------------------------------------------------------

    public function create()
    {
        $this->authorize('create', Payment::class);

        $user = auth()->user();

        // O admin regista pagamentos à mão (formulário completo).
        // O cliente vê as suas marcações por pagar e paga com cartão.
        $appointments = collect();

        if (! $user->isAdmin()) {
            $appointments = Appointment::with(['service', 'barber', 'payments'])
                ->where('customer_id', $user->id)
                ->orderBy('date')
                ->orderBy('time')
                ->get()
                ->filter(fn (Appointment $appointment) => $appointment->canBePaidBy($user))
                ->values();
        }

        return view('payments.create', compact('appointments'));
    }

    // A validação e a autorização estão em GuardarPagamentoRequest.
    public function store(GuardarPagamentoRequest $request)
    {
        $data = $request->validated();

        $appointment = Appointment::with('service')
            ->findOrFail($data['appointment_id']);

        // Só se paga uma marcação que foi feita por nós.
        // O payer_id nunca vem do formulário, vem sempre da sessão.
        if ((int) $appointment->customer_id !== (int) auth()->id()) {
            throw ValidationException::withMessages([
                'appointment_id' => 'Só podes pagar marcações feitas por ti.',
            ]);
        }

        if (auth()->user()->isAdmin()) {
            // O admin regista exatamente o que escreveu.
            $amount = $data['amount'];
            $status = $data['status'];
            $paidAt = $data['paid_at'] ?? null;
        } else {
            // Os outros: o valor é o preço do serviço e fica pendente.
            // Só o admin (ou a Stripe) marca como pago.
            $amount = $appointment->service->price;
            $status = PaymentStatus::Pending->value;
            $paidAt = null;
        }

        Payment::create([
            'appointment_id' => $appointment->id,
            'payer_id'       => auth()->id(),
            'amount'         => $amount,
            'method'         => $data['method'],
            'status'         => $status,
            'paid_at'        => $paidAt,
        ]);

        return redirect()
            ->route('payments.index')
            ->with('success', 'Pagamento criado com sucesso.');
    }

    // ------------------------------------------------------------------
    // Editar
    // ------------------------------------------------------------------

    public function edit(Payment $payment)
    {
        $this->authorize('update', $payment);

        return view('payments.edit', compact('payment'));
    }

    // Só o admin edita pagamentos: a policy (chamada no request) recusa os outros com 403.
    public function update(GuardarPagamentoRequest $request, Payment $payment)
    {
        $data = $request->validated();

        $appointment = Appointment::findOrFail($data['appointment_id']);

        // O payer_id não se edita: o pagamento fica sempre de quem o criou.
        // Por isso a marcação escolhida tem de ser do mesmo cliente.
        if ((int) $appointment->customer_id !== (int) $payment->payer_id) {
            throw ValidationException::withMessages([
                'appointment_id' => 'A marcação indicada não pertence a este pagamento.',
            ]);
        }

        $payment->update([
            'appointment_id' => $appointment->id,
            'amount'         => $data['amount'],
            'method'         => $data['method'],
            'status'         => $data['status'],
            'paid_at'        => $data['paid_at'] ?? null,
        ]);

        return redirect()
            ->route('payments.index')
            ->with('success', 'Pagamento atualizado com sucesso.');
    }

    // ------------------------------------------------------------------
    // Apagar
    // ------------------------------------------------------------------

    public function destroy(Payment $payment)
    {
        $this->authorize('delete', $payment);

        $payment->delete();

        return redirect()
            ->route('payments.index')
            ->with('success', 'Pagamento eliminado com sucesso.');
    }

    // ------------------------------------------------------------------
    // Pagamento com cartão via Stripe
    // ------------------------------------------------------------------

    /**
     * Cria uma sessão de pagamento na Stripe e manda o cliente para lá.
     * O preço vem do serviço da marcação, nunca do pedido.
     */
    public function checkout(Appointment $appointment, StripeGateway $stripe)
    {
        $this->authorize('view', $appointment);

        // Só quem fez a marcação a pode pagar (ver a marcação não chega).
        if ((int) $appointment->customer_id !== (int) auth()->id()) {
            abort(403, 'Só podes pagar marcações feitas por ti.');
        }

        // Evita cobrar duas vezes a mesma marcação.
        if ($this->isAlreadyPaid($appointment)) {
            return back()->with('error', 'Esta marcação já está paga.');
        }

        try {
            $session = $stripe->createCheckoutSession(
                $appointment,
                // A Stripe troca {CHECKOUT_SESSION_ID} pelo id real ao redirecionar.
                route('payments.success', ['appointment' => $appointment->id])
                    . '&session_id={CHECKOUT_SESSION_ID}',
                route('payments.cancel', ['appointment' => $appointment->id])
            );

            return redirect()->away($session->url);
        } catch (ApiConnectionException $e) {
            // Timeout ou falha de rede, mesmo depois das tentativas automáticas.
            Log::warning('Stripe sem resposta (checkout): ' . $e->getMessage());

            return back()->with('error', 'O serviço de pagamento demorou demasiado a responder. Tenta novamente daqui a pouco.');
        } catch (\Exception $e) {
            // O detalhe técnico vai para o log; o cliente vê só uma mensagem simples.
            Log::error('Erro Stripe: ' . $e->getMessage());

            return back()->with('error', 'Serviço de pagamento indisponível.');
        }
    }

    /**
     * O cliente voltou da Stripe. Antes de registar, perguntamos à Stripe
     * se a sessão foi mesmo paga e se é desta marcação.
     *
     * O webhook da Stripe faz o mesmo registo. Como a sessão só gera um
     * pagamento (ver StripePaymentRecorder), não há duplicados.
     */
    public function success(
        Request $request,
        StripeGateway $stripe,
        StripePaymentRecorder $recorder
    ) {
        $appointment = Appointment::findOrFail($request->query('appointment'));

        if ((int) $appointment->customer_id !== (int) auth()->id()) {
            abort(403);
        }

        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return $this->paymentNotConfirmed();
        }

        try {
            $session = $stripe->retrieveSession($sessionId);
        } catch (ApiConnectionException $e) {
            Log::warning('Stripe sem resposta (confirmação): ' . $e->getMessage());

            return $this->paymentNotConfirmed();
        } catch (\Exception $e) {
            Log::error('Erro Stripe ao confirmar pagamento: ' . $e->getMessage());

            return $this->paymentNotConfirmed();
        }

        // A sessão tem de ser desta marcação (senão podia-se usar
        // o id de uma sessão paga de outra marcação).
        if ((string) $session->client_reference_id !== (string) $appointment->id) {
            return $this->paymentNotConfirmed();
        }

        // Devolve null se a sessão ainda não estiver paga.
        $payment = $recorder->record($session);

        if ($payment === null) {
            return $this->paymentNotConfirmed();
        }

        return redirect()
            ->route('payments.index')
            ->with('success', 'Pagamento concluído com sucesso!');
    }

    /**
     * O cliente desistiu na página da Stripe. Não se grava nada.
     */
    public function cancel()
    {
        return redirect()
            ->route('payments.index')
            ->with('error', 'O pagamento foi cancelado.');
    }

    // ------------------------------------------------------------------
    // Auxiliares privados
    // ------------------------------------------------------------------

    private function paymentNotConfirmed()
    {
        return redirect()
            ->route('payments.index')
            ->with('error', 'Não foi possível confirmar o pagamento.');
    }

    // A marcação já tem algum pagamento com estado "paid"?
    private function isAlreadyPaid(Appointment $appointment): bool
    {
        return $appointment->payments()
            ->where('status', PaymentStatus::Paid->value)
            ->exists();
    }
}
