<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

/**
 * Ver serviços é público (rotas index e show, sem política).
 * Criar, editar e apagar é só do admin, que passa no Gate::before
 * (AppServiceProvider). Todos os outros perfis são recusados aqui.
 */
class ServicePolicy
{
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Service $service): bool
    {
        return false;
    }

    public function delete(User $user, Service $service): bool
    {
        return false;
    }
}
