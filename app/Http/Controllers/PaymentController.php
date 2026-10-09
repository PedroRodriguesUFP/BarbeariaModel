<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with([
            'client.user',
            'barber.user',
            'appointment',
        ])->get();

        return view('payments.index', compact('payments'));
    }

    public function create()
    {
        return view('payments.create');
    }

    public function store(Request $request)
    {
        // valida se o formulário é válido
        $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'client_id' => 'required|exists:clients,id',
            'barber_id' => 'nullable|exists:barbers,id',
            'amount' => 'required|numeric|min:0',
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
            'paid_at' => 'nullable|date',
        ]);

        Payment::create([
            'appointment_id' => $request->input('appointment_id'),
            'client_id' => $request->input('client_id'),
            'barber_id' => $request->input('barber_id'),
            'amount' => $request->input('amount'),
            'method' => $request->input('method'),
            'status' => $request->input('status'),
            'paid_at' => $request->input('paid_at'),
        ]);

        return redirect()
            ->route('payments.index')
            ->with('success', 'Payment created successfully.');
    }

    public function show($id)
    {
        $payment = Payment::with([
            'client.user',
            'barber.user',
            'appointment',
        ])->find($id);

        if (! $payment) {
            throw new \Exception(
                'Payment not found, please check if the Payment exists and try again.'
            );
        }

        return view('payments.show', compact('payment'));
    }

    public function edit($id)
    {
        $payment = Payment::find($id);

        if (! $payment) {
            throw new \Exception(
                'Payment not found, please check if the Payment exists and try again.'
            );
        }

        return view('payments.edit', compact('payment'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'client_id' => 'required|exists:clients,id',
            'barber_id' => 'nullable|exists:barbers,id',
            'amount' => 'required|numeric|min:0',
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
            'paid_at' => 'nullable|date',
        ]);

        $payment = Payment::find($id);

        if ($payment) {
            $payment->update([
                'appointment_id' => $request->input('appointment_id'),
                'client_id' => $request->input('client_id'),
                'barber_id' => $request->input('barber_id'),
                'amount' => $request->input('amount'),
                'method' => $request->input('method'),
                'status' => $request->input('status'),
                'paid_at' => $request->input('paid_at'),
            ]);

            return redirect()
                ->route('payments.index')
                ->with('success', 'Payment updated successfully.');
        } else {
            throw new \Exception(
                'Payment not found, please check if the Payment exists and try again.'
            );
        }
    }

    public function destroy($id)
    {
        $payment = Payment::find($id);

        if ($payment) {
            $payment->delete();

            return redirect()
                ->route('payments.index')
                ->with('success', 'Payment deleted successfully.');
        } else {
            throw new \Exception(
                'Payment not found, please check if the Payment exists and try again.'
            );
        }
    }

    public function checkout(int $serviceId)
    {
        $service = Service::findOrFail($serviceId);

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
            'payment_method_types' => ['card'],

            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'eur',

                        'product_data' => [
                            'name' => $service->name,
                        ],

                        'unit_amount' => (int) ($service->price * 100),
                    ],

                    'quantity' => 1,
                ],
            ],

            'mode' => 'payment',

            'success_url' => route('services.index')
                . '?payment=success',

            'cancel_url' => route('services.index')
                . '?payment=cancelled',
        ]);

        return redirect($session->url);
    }
}