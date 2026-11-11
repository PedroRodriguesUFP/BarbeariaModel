<?php

use App\Enums\Status;
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

function makeAppointmentFor(User $client, User $barber, Service $service, Status $status = Status::Pending): Appointment
{
    return Appointment::create([
        'date' => nextMonday(),
        'time' => '10:00',
        'status' => $status,
        'customer_id' => $client->id,
        'service_id' => $service->id,
        'barber_id' => $barber->id,
    ]);
}

test('client cannot open the edit page of another client appointment', function () {
    $client = User::factory()->client()->create();
    $service = Service::factory()->create();
    $barber = makeAvailableBarber($service);
    $appointment = makeAppointmentFor(User::factory()->client()->create(), $barber, $service);

    $this->actingAs($client)
        ->get(route('appointments.edit', $appointment))
        ->assertForbidden();
});

test('barber of the appointment can confirm and then complete it', function () {
    $service = Service::factory()->create();
    $barber = makeAvailableBarber($service);
    $appointment = makeAppointmentFor(User::factory()->client()->create(), $barber, $service);

    $this->actingAs($barber)->patch(route('appointments.confirm', $appointment))->assertRedirect();
    expect($appointment->fresh()->status)->toBe(Status::Confirmed);

    $this->actingAs($barber)->patch(route('appointments.complete', $appointment))->assertRedirect();
    expect($appointment->fresh()->status)->toBe(Status::Completed);
});

test('client cannot confirm or complete an appointment', function () {
    $client = User::factory()->client()->create();
    $service = Service::factory()->create();
    $barber = makeAvailableBarber($service);
    $appointment = makeAppointmentFor($client, $barber, $service);

    $this->actingAs($client)->patch(route('appointments.confirm', $appointment))->assertForbidden();
    $this->actingAs($client)->patch(route('appointments.complete', $appointment))->assertForbidden();

    expect($appointment->fresh()->status)->toBe(Status::Pending);
});

test('another barber cannot confirm an appointment', function () {
    $service = Service::factory()->create();
    $barber = makeAvailableBarber($service);
    $appointment = makeAppointmentFor(User::factory()->client()->create(), $barber, $service);

    $this->actingAs(User::factory()->barber()->create())
        ->patch(route('appointments.confirm', $appointment))
        ->assertForbidden();
});

test('barber cannot confirm an appointment that is already confirmed', function () {
    $service = Service::factory()->create();
    $barber = makeAvailableBarber($service);
    $appointment = makeAppointmentFor(User::factory()->client()->create(), $barber, $service, Status::Confirmed);

    $this->actingAs($barber)
        ->patch(route('appointments.confirm', $appointment))
        ->assertForbidden();
});

test('client can cancel an open appointment but not a completed one', function () {
    $client = User::factory()->client()->create();
    $service = Service::factory()->create();
    $barber = makeAvailableBarber($service);
    $open = makeAppointmentFor($client, $barber, $service);
    $done = makeAppointmentFor($client, $barber, $service, Status::Completed);

    $this->actingAs($client)->patch(route('appointments.cancel', $open))->assertRedirect();
    expect($open->fresh()->status)->toBe(Status::Cancelled);

    $this->actingAs($client)->patch(route('appointments.cancel', $done))->assertForbidden();
    expect($done->fresh()->status)->toBe(Status::Completed);
});

test('admin can confirm any pending appointment', function () {
    $service = Service::factory()->create();
    $barber = makeAvailableBarber($service);
    $appointment = makeAppointmentFor(User::factory()->client()->create(), $barber, $service);

    $this->actingAs(User::factory()->admin()->create())
        ->patch(route('appointments.confirm', $appointment))
        ->assertRedirect();

    expect($appointment->fresh()->status)->toBe(Status::Confirmed);
});
