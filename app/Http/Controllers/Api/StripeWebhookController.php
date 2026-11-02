<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Exception $e) {
            Log::error('Erro no Webhook Stripe: ' . $e->getMessage());
            return response()->json(['error' => 'Assinatura inválida'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $appointmentId = $session->client_reference_id;

            if ($appointmentId) {
                $appointment = Appointment::with('service')->find($appointmentId);
                
                // Evitar duplicar pagamentos se o success_url já o tiver criado
                Payment::firstOrCreate(
                    ['appointment_id' => $appointment->id],
                    [
                        'payer_id' => $appointment->customer_id,
                        'amount' => $appointment->service->price,
                        'method' => 'card',
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]
                );
            }
        }

        return response()->json(['status' => 'success']);
    }
}