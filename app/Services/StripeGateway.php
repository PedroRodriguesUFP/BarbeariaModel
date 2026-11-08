<?php

namespace App\Services;

use App\Models\Appointment;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Stripe;
use Stripe\Webhook;

/**
 * Único sítio que fala diretamente com a biblioteca da Stripe.
 *
 * Os controllers usam esta classe em vez de chamar a Stripe à mão.
 * Assim, nos testes, basta trocar esta classe por uma falsa e nenhum
 * teste faz pedidos reais à Stripe.
 */
class StripeGateway
{
    /**
     * Cria a sessão de pagamento (a página da Stripe onde o cliente paga).
     * O preço vem sempre do serviço da marcação.
     */
    public function createCheckoutSession(
        Appointment $appointment,
        string $successUrl,
        string $cancelUrl
    ): Session {
        $this->configureApiKey();

        $service = $appointment->service;

        return Session::create([
            'client_reference_id'  => $appointment->id,
            'mode'                 => 'payment',

            'line_items' => [[
                'price_data' => [
                    'currency'     => 'eur',
                    'product_data' => [
                        'name' => 'Marcação #' . $appointment->id . ' - ' . $service->name,
                    ],
                    // A Stripe quer o valor em cêntimos.
                    'unit_amount'  => (int) round($service->price * 100),
                ],
                'quantity' => 1,
            ]],

            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
        ]);
    }

    // Vai buscar uma sessão à Stripe, para confirmar se foi mesmo paga.
    public function retrieveSession(string $sessionId): Session
    {
        $this->configureApiKey();

        return Session::retrieve($sessionId);
    }

    /**
     * Valida a assinatura de um webhook e devolve o evento.
     * Lança uma exceção se a assinatura for falsa ou o corpo estiver alterado.
     */
    public function constructWebhookEvent(string $payload, ?string $signature): Event
    {
        return Webhook::constructEvent(
            $payload,
            (string) $signature,
            (string) config('services.stripe.webhook_secret')
        );
    }

    private function configureApiKey(): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }
}
