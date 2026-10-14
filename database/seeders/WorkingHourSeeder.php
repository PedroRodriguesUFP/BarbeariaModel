<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class WorkingHourSeeder extends Seeder
{
    public function run(): void
    {
        $barbers = User::where(
            'role',
            UserRole::Barber->value
        )->get();

        foreach ($barbers as $barber) {

            // Segunda a sexta
            for ($day = 1; $day <= 5; $day++) {

                $barber->workingHours()->create([
                    'day_of_week' => $day,
                    'start_time' => '09:00',
                    'end_time' => '13:00',
                ]);

                $barber->workingHours()->create([
                    'day_of_week' => $day,
                    'start_time' => '14:00',
                    'end_time' => '18:00',
                ]);
            }

            // Sábado
            $barber->workingHours()->create([
                'day_of_week' => 6,
                'start_time' => '09:00',
                'end_time' => '13:00',
            ]);
        }
    }
}