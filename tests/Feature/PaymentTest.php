<?php

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;

function makeAppointmentFor(User $customer): Appointment
{
    $service = Service::factory()->create();
    $barber = User::factory()->barber()->create();

    return Appointment::create([
        'date' => now()->addDay()->toDateString(),
        'time' => '10:00',
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'barber_id' => $barber->id,
    ]);
}

test('guest is redirected to login when listing payments', function () {
    $this->get(route('payments.index'))
        ->assertRedirect(route('login'));
});

test('client can pay their own appointment and payer_id is forced server side', function () {
    $client = User::factory()->client()->create();
    $impersonated = User::factory()->client()->create();
    $appointment = makeAppointmentFor($client);

    $response = $this->actingAs($client)->post(route('payments.store'), [
        'appointment_id' => $appointment->id,
        // payer_id spoofado deliberadamente: deve ser ignorado.
        'payer_id' => $impersonated->id,
        'amount' => 25,
        'method' => 'card',
        'status' => 'paid',
    ]);

    $response->assertRedirect(route('payments.index'));

    $this->assertDatabaseHas('payments', [
        'appointment_id' => $appointment->id,
        'payer_id' => $client->id,
    ]);

    $this->assertDatabaseMissing('payments', [
        'appointment_id' => $appointment->id,
        'payer_id' => $impersonated->id,
    ]);
});

test('client cannot pay an appointment that is not theirs', function () {
    $client = User::factory()->client()->create();
    $otherClient = User::factory()->client()->create();
    $appointment = makeAppointmentFor($otherClient);

    $this->actingAs($client)->post(route('payments.store'), [
        'appointment_id' => $appointment->id,
        'amount' => 25,
        'method' => 'card',
        'status' => 'paid',
    ])->assertSessionHasErrors('appointment_id');

    $this->assertDatabaseMissing('payments', [
        'appointment_id' => $appointment->id,
    ]);
});

test('barber cannot create a payment', function () {
    $barber = User::factory()->barber()->create();
    $client = User::factory()->client()->create();
    $appointment = makeAppointmentFor($client);

    $this->actingAs($barber)->post(route('payments.store'), [
        'appointment_id' => $appointment->id,
        'amount' => 25,
        'method' => 'card',
        'status' => 'paid',
    ])->assertForbidden();
});

test('client only sees their own payments in the index', function () {
    $client = User::factory()->client()->create();
    $otherClient = User::factory()->client()->create();

    $mine = Payment::create([
        'appointment_id' => makeAppointmentFor($client)->id,
        'payer_id' => $client->id,
        'amount' => 20,
        'method' => 'cash',
        'status' => 'paid',
    ]);

    $notMine = Payment::create([
        'appointment_id' => makeAppointmentFor($otherClient)->id,
        'payer_id' => $otherClient->id,
        'amount' => 30,
        'method' => 'cash',
        'status' => 'paid',
    ]);

    $response = $this->actingAs($client)->get(route('payments.index'));

    $response->assertOk();
    $response->assertSee(route('payments.show', $mine->id), false);
    $response->assertDontSee(route('payments.show', $notMine->id), false);
});

test('client cannot view another client payment', function () {
    $client = User::factory()->client()->create();
    $otherClient = User::factory()->client()->create();

    $payment = Payment::create([
        'appointment_id' => makeAppointmentFor($otherClient)->id,
        'payer_id' => $otherClient->id,
        'amount' => 30,
        'method' => 'cash',
        'status' => 'paid',
    ]);

    $this->actingAs($client)
        ->get(route('payments.show', $payment))
        ->assertForbidden();
});

test('client cannot delete another client payment', function () {
    $client = User::factory()->client()->create();
    $otherClient = User::factory()->client()->create();

    $payment = Payment::create([
        'appointment_id' => makeAppointmentFor($otherClient)->id,
        'payer_id' => $otherClient->id,
        'amount' => 30,
        'method' => 'cash',
        'status' => 'paid',
    ]);

    $this->actingAs($client)
        ->delete(route('payments.destroy', $payment))
        ->assertForbidden();

    $this->assertDatabaseHas('payments', ['id' => $payment->id]);
});

test('admin can view and manage any payment', function () {
    $admin = User::factory()->admin()->create();
    $client = User::factory()->client()->create();

    $payment = Payment::create([
        'appointment_id' => makeAppointmentFor($client)->id,
        'payer_id' => $client->id,
        'amount' => 30,
        'method' => 'cash',
        'status' => 'paid',
    ]);

    $this->actingAs($admin)
        ->get(route('payments.show', $payment))
        ->assertOk();

    $this->actingAs($admin)
        ->delete(route('payments.destroy', $payment))
        ->assertRedirect(route('payments.index'));

    $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
});
