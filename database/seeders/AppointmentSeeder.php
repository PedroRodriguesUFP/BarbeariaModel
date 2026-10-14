<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where(
            'role',
            UserRole::Client->value
        )->first();

        $barber = User::where(
            'role',
            UserRole::Barber->value
        )->first();

        $service = Service::first();

        if (! $customer || ! $barber || ! $service) {
            return;
        }

        Appointment::create([
            'customer_id' => $customer->id,
            'barber_id' => $barber->id,
            'service_id' => $service->id,
            'date' => now()->addDay()->toDateString(),
            'time' => '10:00',
            'status' => Status::Pending->value,
        ]);
    }
}