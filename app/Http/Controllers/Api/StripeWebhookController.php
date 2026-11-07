<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StripeGateway;
use App\Services\StripePaymentRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Recebe os avisos da Stripe (webhook).
 *
 * Serve de rede de segurança: se o cliente pagar e fechar o separador
 * antes de voltar ao site, é aqui que o pagamento fica gravado.
 *
 * A Stripe repete o aviso enquanto não receber 2xx, por isso só
 * devolvemos erro quando a assinatura é inválida.
 */
class StripeWebhookController extends Controller
{
    // Eventos que significam "o dinheiro entrou".
    private const PAID_EVENTS = [
        'checkout.session.completed',
        'checkout.session.async_payment_succeeded',
    ];

    public function handle(
        Request $request,
        StripeGateway $stripe,
        StripePaymentRecorder $recorder
    ): JsonResponse {
        try {
            $event = $stripe->constructWebhookEvent(
                $request->getContent(),
                $request->header('Stripe-Signature')
            );
        } catch (\Exception $e) {
            Log::error('Erro no Webhook Stripe: ' . $e->getMessage());

            return response()->json(['error' => 'Assinatura inválida'], 400);
        }

        if (in_array($event->type, self::PAID_EVENTS, true)) {
            $session = $event->data->object;

            // Gravar é seguro repetir: a mesma sessão nunca cria dois pagamentos.
            if ($recorder->record($session) === null) {
                Log::info('Webhook Stripe sem pagamento a gravar.', [
                    'session' => $session->id ?? null,
                    'reference' => $session->client_reference_id ?? null,
                ]);
            }
        }

        return response()->json(['status' => 'success']);
    }
}
