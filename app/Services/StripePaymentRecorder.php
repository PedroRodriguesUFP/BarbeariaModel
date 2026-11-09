<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Payment;

/**
 * Grava no nosso sistema um pagamento feito na Stripe.
 *
 * Tanto o regresso do cliente (PaymentController::success) como o webhook
 * da Stripe passam por aqui. Cada sessão da Stripe só gera um pagamento,
 * não importa quantas vezes ou por que ordem chegue (a coluna
 * stripe_session_id é única).
 */
class StripePaymentRecorder
{
    /**
     * @param  object  $session  Sessão da Stripe (id, payment_status, client_reference_id, amount_total).
     * @return Payment|null null se a sessão ainda não está paga ou a marcação não existe.
     */
    public function record(object $session): ?Payment
    {
        if ($session->payment_status !== 'paid') {
            return null;
        }

        $appointment = Appointment::find($session->client_reference_id);

        // Sem cliente com conta (walk-in) não há payer_id para gravar.
        if ($appointment === null || $appointment->customer_id === null) {
            return null;
        }

        // Se esta sessão já foi gravada, devolve o pagamento que já existe.
        return Payment::createOrFirst(
            ['stripe_session_id' => $session->id],
            [
                'appointment_id' => $appointment->id,
                'payer_id'       => $appointment->customer_id,
                // Valor realmente cobrado pela Stripe (vem em cêntimos).
                'amount'         => $session->amount_total / 100,
                'method'         => PaymentMethod::Card->value,
                'status'         => PaymentStatus::Paid->value,
                'paid_at'        => now(),
            ]
        );
    }
}
