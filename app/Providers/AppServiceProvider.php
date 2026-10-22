<?php
namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
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
        Gate::before(function (User $user, string $ability) {
            return $user->role === UserRole::Admin ? true : null;
        });

        Gate::define('viewBarberCalendar', function (User $user, User $barber) {
            return $user->id === $barber->id;
        });
    }
}