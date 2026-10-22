<?php

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;

function makeAvailableBarber(Service $service): User
{
    $barber = User::factory()->barber()->create();

    $barber->services()->attach($service->id);

    $barber->workingHours()->create([
        'day_of_week' => 1,
        'start_time' => '09:00:00',
        'end_time' => '18:00:00',
    ]);

    return $barber;
}

function nextMonday(): string
{
    return now()->next('Monday')->toDateString();
}

test('guest is redirected to login when listing appointments', function () {
    $this->get(route('appointments.index'))
        ->assertRedirect(route('login'));
});

test('client can create an appointment for an available barber', function () {
    $client = User::factory()->client()->create();
    $service = Service::factory()->create(['duration_minutes' => 30]);
    $barber = makeAvailableBarber($service);

    $response = $this->actingAs($client)->post(route('appointments.store'), [
        'date' => nextMonday(),
        'time' => '10:00',
        'barber_id' => $barber->id,
        'service_id' => $service->id,
    ]);

    $response->assertRedirect(route('appointments.index'));

    $this->assertDatabaseHas('appointments', [
        'customer_id' => $client->id,
        'barber_id' => $barber->id,
        'service_id' => $service->id,
    ]);
});

test('barber cannot create an appointment as a customer', function () {
    $barber = User::factory()->barber()->create();
    $service = Service::factory()->create();
    $otherBarber = makeAvailableBarber($service);

    $this->actingAs($barber)->post(route('appointments.store'), [
        'date' => nextMonday(),
        'time' => '10:00',
        'barber_id' => $otherBarber->id,
        'service_id' => $service->id,
    ])->assertForbidden();
});

test('client only sees their own appointments in the index', function () {
    $client = User::factory()->client()->create();
    $otherClient = User::factory()->client()->create();
    $service = Service::factory()->create();
    $barber = makeAvailableBarber($service);

    $mine = Appointment::create([
        'date' => nextMonday(),
        'time' => '10:00',
        'customer_id' => $client->id,
        'service_id' => $service->id,
        'barber_id' => $barber->id,
    ]);

    $notMine = Appointment::create([
        'date' => nextMonday(),
        'time' => '11:00',
        'customer_id' => $otherClient->id,
        'service_id' => $service->id,
        'barber_id' => $barber->id,
    ]);

    $response = $this->actingAs($client)->get(route('appointments.index'));

    $response->assertOk();
    $response->assertSee(route('appointments.show', $mine->id), false);
    $response->assertDontSee(route('appointments.show', $notMine->id), false);
});

test('client cannot view another client appointment', function () {
    $client = User::factory()->client()->create();
    $otherClient = User::factory()->client()->create();
    $service = Service::factory()->create();
    $barber = makeAvailableBarber($service);

    $appointment = Appointment::create([
        'date' => nextMonday(),
        'time' => '10:00',
        'customer_id' => $otherClient->id,
        'service_id' => $service->id,
        'barber_id' => $barber->id,
    ]);

    $this->actingAs($client)
        ->get(route('appointments.show', $appointment))
        ->assertForbidden();
});

test('client cannot delete another client appointment', function () {
    $client = User::factory()->client()->create();
    $otherClient = User::factory()->client()->create();
    $service = Service::factory()->create();
    $barber = makeAvailableBarber($service);

    $appointment = Appointment::create([
        'date' => nextMonday(),
        'time' => '10:00',
        'customer_id' => $otherClient->id,
        'service_id' => $service->id,
        'barber_id' => $barber->id,
    ]);

    $this->actingAs($client)
        ->delete(route('appointments.destroy', $appointment))
        ->assertForbidden();

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
});
