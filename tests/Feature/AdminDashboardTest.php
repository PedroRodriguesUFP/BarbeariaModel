<?php

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;

test('admin dashboard opens with data', function () {
    $admin  = User::factory()->admin()->create();
    $client = User::factory()->client()->create();
    $barber = User::factory()->barber()->create();

    $appointment = Appointment::create([
        'date'        => now()->toDateString(),
        'time'        => '10:00',
        'customer_id' => $client->id,
        'service_id'  => Service::factory()->create()->id,
        'barber_id'   => $barber->id,
    ]);

    Payment::create([
        'appointment_id' => $appointment->id,
        'payer_id'       => $client->id,
        'amount'         => 25,
        'method'         => 'card',
        'status'         => 'paid',
        'paid_at'        => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('admin dashboard opens on an empty database', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('service knows its appointments', function () {
    $service = Service::factory()->create();
    $client  = User::factory()->client()->create();

    Appointment::create([
        'date'        => now()->addDay()->toDateString(),
        'time'        => '10:00',
        'customer_id' => $client->id,
        'service_id'  => $service->id,
        'barber_id'   => User::factory()->barber()->create()->id,
    ]);

    expect($service->appointments()->count())->toBe(1);
});

test('client is forbidden from the admin dashboard', function () {
    $client = User::factory()->client()->create();

    $this->actingAs($client)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});
