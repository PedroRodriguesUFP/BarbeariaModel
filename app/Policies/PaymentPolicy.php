<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        // O controller filtra a listagem pelos pagamentos
        // do próprio utilizador; o admin vê tudo via before().
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

    public function update(User $user, Payment $payment): bool
    {
        return $user->id === $payment->payer_id;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->id === $payment->payer_id;
    }
}
