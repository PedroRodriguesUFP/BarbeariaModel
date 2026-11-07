<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Stripe\Checkout\Session;
use Stripe\Stripe;

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

    public function show($id)
    {
        $payment = Payment::with([
            'payer',
            'appointment.barber',
            'appointment.service',
        ])->findOrFail($id);

        $this->authorize('view', $payment);

        return view('payments.show', compact('payment'));
    }

    // ------------------------------------------------------------------
    // Criar (registo manual)
    // ------------------------------------------------------------------

    public function create()
    {
        $this->authorize('create', Payment::class);

        return view('payments.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Payment::class);

        $data = $this->validatePayment($request);

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

    public function edit($id)
    {
        $payment = Payment::findOrFail($id);

        $this->authorize('update', $payment);

        return view('payments.edit', compact('payment'));
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        $this->authorize('update', $payment);

        $data = $this->validatePayment($request);

        // Quem não é admin só pode mudar a forma de pagamento.
        if (! auth()->user()->isAdmin()) {
            $payment->update(['method' => $data['method']]);

            return redirect()
                ->route('payments.index')
                ->with('success', 'Pagamento atualizado com sucesso.');
        }

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

    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);

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
    public function checkout(Appointment $appointment)
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
            Stripe::setApiKey(config('services.stripe.secret'));

            $service = $appointment->service;

            $session = Session::create([
                'payment_method_types' => ['card'],
                'client_reference_id'  => $appointment->id,
                'mode'                 => 'payment',

                'line_items' => [[
                    'price_data' => [
                        'currency'     => 'eur',
                        'product_data' => [
                            'name' => 'Marcação #' . $appointment->id . ' - ' . $service->name,
                        ],
                        // A Stripe quer o valor em cêntimos.
                        'unit_amount'  => (int) ($service->price * 100),
                    ],
                    'quantity' => 1,
                ]],

                // A Stripe troca {CHECKOUT_SESSION_ID} pelo id real ao redirecionar.
                'success_url' => route('payments.success', ['appointment' => $appointment->id])
                    . '&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('payments.cancel', ['appointment' => $appointment->id]),
            ]);

            return redirect()->away($session->url);
        } catch (\Exception $e) {
            // O detalhe técnico vai para o log; o cliente vê só uma mensagem simples.
            Log::error('Erro Stripe: ' . $e->getMessage());

            return back()->with('error', 'Serviço de pagamento indisponível.');
        }
    }

    /**
     * O cliente voltou da Stripe. Antes de registar, perguntamos à Stripe
     * se a sessão foi mesmo paga e se é desta marcação.
     * Recarregar a página não cria um segundo pagamento.
     */
    public function success(Request $request)
    {
        $appointment = Appointment::with('service')
            ->findOrFail($request->query('appointment'));

        if ((int) $appointment->customer_id !== (int) auth()->id()) {
            abort(403);
        }

        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()
                ->route('payments.index')
                ->with('error', 'Não foi possível confirmar o pagamento.');
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $session = Session::retrieve($sessionId);
        } catch (\Exception $e) {
            Log::error('Erro Stripe ao confirmar pagamento: ' . $e->getMessage());

            return redirect()
                ->route('payments.index')
                ->with('error', 'Não foi possível confirmar o pagamento.');
        }

        // Pago na Stripe e a sessão pertence a esta marcação.
        $isPaid = $session->payment_status === 'paid'
            && (string) $session->client_reference_id === (string) $appointment->id;

        if (! $isPaid) {
            return redirect()
                ->route('payments.index')
                ->with('error', 'Não foi possível confirmar o pagamento.');
        }

        if (! $this->isAlreadyPaid($appointment)) {
            Payment::create([
                'appointment_id' => $appointment->id,
                'payer_id'       => auth()->id(),
                // Valor realmente cobrado pela Stripe (vem em cêntimos).
                'amount'         => $session->amount_total / 100,
                'method'         => PaymentMethod::Card->value,
                'status'         => PaymentStatus::Paid->value,
                'paid_at'        => now(),
            ]);
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

    // A marcação já tem algum pagamento com estado "paid"?
    private function isAlreadyPaid(Appointment $appointment): bool
    {
        return $appointment->payments()
            ->where('status', PaymentStatus::Paid->value)
            ->exists();
    }

    /**
     * Validação para criar e editar.
     * O admin preenche tudo. Os outros só enviam marcação e forma de pagamento.
     */
    private function validatePayment(Request $request): array
    {
        $rules = [
            'appointment_id' => ['required', 'exists:appointments,id'],
            'method'         => ['required', Rule::enum(PaymentMethod::class)],
        ];

        if (auth()->user()->isAdmin()) {
            $rules += [
                'amount'  => ['required', 'numeric', 'min:0'],
                'status'  => ['required', Rule::enum(PaymentStatus::class)],
                'paid_at' => ['nullable', 'date'],
            ];
        }

        return $request->validate($rules);
    }
}
