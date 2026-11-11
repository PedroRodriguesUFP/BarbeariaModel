<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

/**
 * Quem pode fazer o quê com uma marcação.
 *
 * O admin passa em tudo no Gate::before (AppServiceProvider),
 * por isso aqui só há regras de cliente e de barbeiro.
 */
class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $this->isInvolved($user, $appointment);
    }

    public function create(User $user): bool
    {
        return $user->isClient();
    }

    public function createWalkIn(User $user): bool
    {
        return $user->isBarber();
    }

    // Só o barbeiro da marcação confirma, e só se estiver pendente.
    public function confirm(User $user, Appointment $appointment): bool
    {
        return $this->isBarberOf($user, $appointment)
            && $appointment->canBeConfirmed();
    }

    // Só o barbeiro da marcação conclui, e só se estiver confirmada.
    public function complete(User $user, Appointment $appointment): bool
    {
        return $this->isBarberOf($user, $appointment)
            && $appointment->canBeCompleted();
    }

    // O cliente ou o barbeiro cancelam, enquanto estiver em aberto.
    public function cancel(User $user, Appointment $appointment): bool
    {
        return $this->isInvolved($user, $appointment)
            && $appointment->canBeCancelled();
    }

    // Editar e apagar seguem a regra de cancelar.
    public function update(User $user, Appointment $appointment): bool
    {
        return $this->cancel($user, $appointment);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $this->cancel($user, $appointment);
    }

    private function isCustomerOf(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->customer_id;
    }

    private function isBarberOf(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->barber_id;
    }

    private function isInvolved(User $user, Appointment $appointment): bool
    {
        return $this->isCustomerOf($user, $appointment)
            || $this->isBarberOf($user, $appointment);
    }
}
