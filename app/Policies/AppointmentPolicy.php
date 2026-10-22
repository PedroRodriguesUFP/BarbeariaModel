<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
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
        return true;
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->customer_id
            || $user->id === $appointment->barber_id;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Client;
    }

    public function update(User $user, Appointment $appointment): bool
    {
        if ($user->id === $appointment->customer_id) {
            return $appointment->canBeCancelled();
        }

        return $user->id === $appointment->barber_id;
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $this->update($user, $appointment);
    }
        public function createWalkIn(User $user): bool
    {
        return $user->role === UserRole::Barber;
    }
}