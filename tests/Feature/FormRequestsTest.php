<?php

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;

function requestAppointmentFor(User $client): Appointment
{
    return Appointment::create([
        'date'        => now()->addDay()->toDateString(),
        'time'        => '10:00',
        'customer_id' => $client->id,
        'service_id'  => Service::factory()->create()->id,
        'barber_id'   => User::factory()->barber()->create()->id,
    ]);
}

test('another client cannot update an appointment (refused by the form request)', function () {
    $owner       = User::factory()->client()->create();
    $intruder    = User::factory()->client()->create();
    $appointment = requestAppointmentFor($owner);

    $this->actingAs($intruder)
        ->put(route('appointments.update', $appointment), [
            'date'       => now()->addDays(2)->toDateString(),
            'time'       => '11:00',
            'barber_id'  => $appointment->barber_id,
            'service_id' => $appointment->service_id,
        ])
        ->assertForbidden();
});

test('a barber cannot create an appointment as if they were a customer', function () {
    $barber = User::factory()->barber()->create();

    $this->actingAs($barber)
        ->post(route('appointments.store'), [])
        ->assertForbidden();
});

test('admin creating an appointment must choose the customer', function () {
    $admin   = User::factory()->admin()->create();
    $service = Service::factory()->create();
    $barber  = User::factory()->barber()->create();

    $this->actingAs($admin)
        ->post(route('appointments.store'), [
            'date'       => now()->addDay()->toDateString(),
            'time'       => '10:00',
            'barber_id'  => $barber->id,
            'service_id' => $service->id,
        ])
        ->assertSessionHasErrors('customer_id');
});

test('appointment validation errors are shown in portuguese', function () {
    $client = User::factory()->client()->create();

    $this->actingAs($client)
        ->post(route('appointments.store'), [])
        ->assertSessionHasErrors([
            'date'       => 'Indique a data.',
            'time'       => 'Indique a hora.',
            'barber_id'  => 'Escolha um barbeiro.',
            'service_id' => 'Escolha um serviço.',
        ]);
});

test('a client cannot create or edit services', function () {
    $client  = User::factory()->client()->create();
    $service = Service::factory()->create();

    $valid = ['name' => 'Corte', 'price' => 10, 'duration_minutes' => 30, 'category' => 'hair'];

    $this->actingAs($client)->post(route('services.store'), $valid)->assertForbidden();
    $this->actingAs($client)->put(route('services.update', $service), $valid)->assertForbidden();
});

test('service validation rejects a negative price and a zero duration', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('services.store'), [
            'name'             => 'Corte',
            'price'            => -5,
            'duration_minutes' => 0,
            'category'         => 'hair',
        ])
        ->assertSessionHasErrors(['price', 'duration_minutes']);
});

test('a non admin cannot edit a payment (refused by the form request)', function () {
    $client      = User::factory()->client()->create();
    $appointment = requestAppointmentFor($client);

    $payment = Payment::create([
        'appointment_id' => $appointment->id,
        'payer_id'       => $client->id,
        'amount'         => 25,
        'method'         => 'cash',
        'status'         => 'pending',
    ]);

    $this->actingAs($client)
        ->put(route('payments.update', $payment), [
            'appointment_id' => $appointment->id,
            'method'         => 'card',
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('payments', ['id' => $payment->id, 'method' => 'cash']);
});

test('a missing record gives 404 through route model binding', function () {
    $client = User::factory()->client()->create();

    $this->actingAs($client)->get(route('appointments.show', 999999))->assertNotFound();
});
