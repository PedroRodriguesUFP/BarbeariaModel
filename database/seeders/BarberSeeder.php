<?php

namespace Database\Seeders;
use App\Models\Barber;

use App\Enums\UserRole;
use App\Models\Barber;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class BarberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Barber::create([
            'name' => 'Carlos Tesoura',
            'email' => 'carlos@barbershop.com',
            'phone' => '923456789',
            'id' => 1,
        ]);
    }
}
