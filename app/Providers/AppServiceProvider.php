<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Listeners\RegistarEventosDeAutenticacao;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Fora de produção, aceder a uma relação que não foi carregada com with()
        // lança uma exceção. É assim que se descobrem os N+1.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Botões de página com o estilo do site (em vez do desenho Tailwind por defeito).
        Paginator::defaultView('vendor.pagination.barbearia');

        // O admin passa em todas as verificações, de todas as policies.
        // Por isso as policies não repetem esta regra.
        Gate::before(function (User $user, string $ability) {
            return $user->role === UserRole::Admin ? true : null;
        });

        Gate::define('viewBarberCalendar', function (User $user, User $barber) {
            return $user->id === $barber->id;
        });

        // Regista logins, falhas, bloqueios e logouts (ver o ficheiro do listener).
        Event::subscribe(RegistarEventosDeAutenticacao::class);
    }
}
