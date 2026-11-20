<?php

use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\ServiceController;
use Illuminate\Support\Facades\Route;

// O prefixo /api é automático. Documentação: docs/api.md

// Webhook da Stripe: sem CSRF; a segurança é a assinatura validada no controller.
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

Route::prefix('v1')->name('api.')->group(function () {
    // Obter token (limite de tentativas contra força bruta).
    Route::post('/auth/token', [AuthTokenController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('token.store');

    // Tudo o resto exige "Authorization: Bearer <token>".
    Route::middleware(['auth.token', 'throttle:60,1'])->group(function () {
        Route::delete('/auth/token', [AuthTokenController::class, 'destroy'])->name('token.destroy');
        Route::get('/me', [AuthTokenController::class, 'me'])->name('me');

        Route::apiResource('services', ServiceController::class);
        Route::apiResource('appointments', AppointmentController::class);
    });
});
