<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class BarberSeeder extends Seeder
{
    public function run(): void
    {
        $barber = User::where(
            'role',
            UserRole::Barber->value
        )->firstOrFail();

        $barber->services()->sync(
            Service::pluck('id')
        );
    }
}