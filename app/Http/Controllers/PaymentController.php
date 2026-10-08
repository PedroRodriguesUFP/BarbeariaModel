<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class PaymentController extends Controller
{
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