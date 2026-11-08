<?php

use App\Http\Controllers\Api\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// URL final: POST /api/stripe/webhook (o prefixo /api é automático).
// Não precisa de CSRF: as rotas da API não o usam. A segurança é a
// assinatura da Stripe, validada no controller.
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);
