<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $appointment = Appointment::with('service')
            ->first();

        if (! $appointment) {
            return;
        }

        Payment::create([
            'appointment_id' => $appointment->id,
            'payer_id' => $appointment->customer_id,
            'amount' => $appointment->service?->price ?? 12.00,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }
}