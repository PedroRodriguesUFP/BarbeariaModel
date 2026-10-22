<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class PaymentController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Payment::class);

        $payments = Payment::with([
            'payer',
            'appointment.barber',
            'appointment.service',
        ])
            ->when(
                ! auth()->user()->isAdmin(),
                fn ($query) => $query->where(
                    'payer_id',
                    auth()->id()
                )
            )
            ->get();

        return view('payments.index', compact('payments'));
    }

    public function create()
    {
        $this->authorize('create', Payment::class);

        return view('payments.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Payment::class);

        $request->validate([
            'appointment_id' => [
                'required',
                'exists:appointments,id',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0',
            ],

            'method' => [
                'required',
                Rule::in([
                    'cash',
                    'card',
                    'mbway',
                    'transfer',
                ]),
            ],

            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'paid',
                    'failed',
                    'refunded',
                ]),
            ],

            'paid_at' => [
                'nullable',
                'date',
            ],
        ]);

        $appointment = Appointment::findOrFail(
            $request->input('appointment_id')
        );

        // O pagamento só pode ser feito pelo utilizador
        // autenticado que efetuou a marcação. payer_id
        // nunca vem do formulário, vem sempre da sessão.
        if (
            (int) $appointment->customer_id !==
            (int) auth()->id()
        ) {
            throw ValidationException::withMessages([
                'appointment_id' =>
                    'Só podes pagar marcações feitas por ti.',
            ]);
        }

        Payment::create([
            'appointment_id' => $appointment->id,
            'payer_id' => auth()->id(),
            'amount' => $request->input('amount'),
            'method' => $request->input('method'),
            'status' => $request->input('status'),
            'paid_at' => $request->input('paid_at'),
        ]);

        return redirect()
            ->route('payments.index')
            ->with(
                'success',
                'Pagamento criado com sucesso.'
            );
    }

    public function show($id)
    {
        $payment = Payment::with([
            'payer',
            'appointment.barber',
            'appointment.service',
        ])->findOrFail($id);

        $this->authorize('view', $payment);

        return view(
            'payments.show',
            compact('payment')
        );
    }

    public function edit($id)
    {
        $payment = Payment::findOrFail($id);

        $this->authorize('update', $payment);

        return view(
            'payments.edit',
            compact('payment')
        );
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        $this->authorize('update', $payment);

        $request->validate([
            'appointment_id' => [
                'required',
                'exists:appointments,id',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0',
            ],

            'method' => [
                'required',
                Rule::in([
                    'cash',
                    'card',
                    'mbway',
                    'transfer',
                ]),
            ],

            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'paid',
                    'failed',
                    'refunded',
                ]),
            ],

            'paid_at' => [
                'nullable',
                'date',
            ],
        ]);

        $appointment = Appointment::findOrFail(
            $request->input('appointment_id')
        );

        // payer_id não é editável: o pagamento continua
        // associado a quem o criou originalmente.
        if (
            (int) $appointment->customer_id !==
            (int) $payment->payer_id
        ) {
            throw ValidationException::withMessages([
                'appointment_id' =>
                    'A marcação indicada não pertence a este pagamento.',
            ]);
        }

        $payment->update([
            'appointment_id' => $appointment->id,
            'amount' => $request->input('amount'),
            'method' => $request->input('method'),
            'status' => $request->input('status'),
            'paid_at' => $request->input('paid_at'),
        ]);

        return redirect()
            ->route('payments.index')
            ->with(
                'success',
                'Pagamento atualizado com sucesso.'
            );
    }

    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);

        $this->authorize('delete', $payment);

        $payment->delete();

        return redirect()
            ->route('payments.index')
            ->with(
                'success',
                'Pagamento eliminado com sucesso.'
            );
    }

    // Mantemos o checkout Stripe que já existia.
    public function checkout(int $serviceId)
    {
        $service = Service::findOrFail($serviceId);

        Stripe::setApiKey(
            config('services.stripe.secret')
        );

        $session = Session::create([
            'payment_method_types' => [
                'card',
            ],

            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'eur',

                        'product_data' => [
                            'name' => $service->name,
                        ],

                        'unit_amount' =>
                            (int) ($service->price * 100),
                    ],

                    'quantity' => 1,
                ],
            ],

            'mode' => 'payment',

            'success_url' =>
                route('services.index')
                . '?payment=success',

            'cancel_url' =>
                route('services.index')
                . '?payment=cancelled',
        ]);

        return redirect($session->url);
    }
}