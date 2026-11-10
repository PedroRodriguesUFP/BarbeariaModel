<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        // O controller filtra a listagem pelos pagamentos do próprio utilizador.
        // O admin vê tudo: passa no Gate::before (AppServiceProvider).
        return true;
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->id === $payment->payer_id;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Client;
    }

    // Só o admin edita ou apaga pagamentos (passa no Gate::before).
    // O cliente vê os seus pagamentos, mas não os altera: senão podia
    // apagar ou mudar um pagamento já pago.
    public function update(User $user, Payment $payment): bool
    {
        return false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }
}
