<?php

namespace Database\Seeders;

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
        $user = User::where('role', UserRole::Barber->value)->firstOrFail();

        $barber = Barber::create([
            'user_id' => $user->id,
            'phone' => '923456789',
            'bio' => 'Professional barber with experience in classic and modern cuts.',
        ]);

        // Associate the barber with the existing services (many-to-many).
        $barber->services()->attach(Service::pluck('id'));
    }
}
