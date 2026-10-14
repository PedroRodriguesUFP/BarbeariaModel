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
        $payments = Payment::with([
            'payer',
            'appointment.barber',
            'appointment.service',
        ])->get();

        return view('payments.index', compact('payments'));
    }

    public function create()
    {
        return view('payments.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'appointment_id' => [
                'required',
                'exists:appointments,id',
            ],

            'payer_id' => [
                'required',
                'exists:users,id',
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

        // O pagamento deve ser feito pelo utilizador
        // que efetuou a marcação.
        if (
            (int) $appointment->customer_id !==
            (int) $request->input('payer_id')
        ) {
            throw ValidationException::withMessages([
                'payer_id' =>
                    'O utilizador indicado não corresponde ao utilizador da marcação.',
            ]);
        }

        Payment::create([
            'appointment_id' => $appointment->id,
            'payer_id' => $request->input('payer_id'),
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

        return view(
            'payments.show',
            compact('payment')
        );
    }

    public function edit($id)
    {
        $payment = Payment::findOrFail($id);

        return view(
            'payments.edit',
            compact('payment')
        );
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'appointment_id' => [
                'required',
                'exists:appointments,id',
            ],

            'payer_id' => [
                'required',
                'exists:users,id',
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

        $payment = Payment::findOrFail($id);

        $appointment = Appointment::findOrFail(
            $request->input('appointment_id')
        );

        if (
            (int) $appointment->customer_id !==
            (int) $request->input('payer_id')
        ) {
            throw ValidationException::withMessages([
                'payer_id' =>
                    'O utilizador indicado não corresponde ao utilizador da marcação.',
            ]);
        }

        $payment->update([
            'appointment_id' => $appointment->id,
            'payer_id' => $request->input('payer_id'),
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