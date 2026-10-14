<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
    $this->call([
    ServiceSeeder::class,
    UserSeeder::class,
    BarberSeeder::class,
    WorkingHourSeeder::class,
    AppointmentSeeder::class,
    ReviewSeeder::class,
    PaymentSeeder::class,
    ]);
    }
}