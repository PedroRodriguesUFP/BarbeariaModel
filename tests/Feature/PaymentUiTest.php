<?php

use App\Enums\PaymentStatus;
use App\Enums\Status;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;

// Testes das páginas: o cliente tem de ver o botão "Pagar com cartão"
// nas marcações dele, e só nas que ainda se podem pagar.

function uiAppointmentFor(User $customer): Appointment
{
    return Appointment::create([
        'date'        => now()->addDay()->toDateString(),
        'time'        => '10:00',
        'customer_id' => $customer->id,
        'service_id'  => Service::factory()->create(['price' => 25])->id,
        'barber_id'   => User::factory()->barber()->create()->id,
    ]);
}

test('client sees the pay button on their appointment page', function () {
    $client = User::factory()->client()->create();
    $appointment = uiAppointmentFor($client);

    $this->actingAs($client)
        ->get(route('appointments.show', $appointment))
        ->assertOk()
        ->assertSee('Pagar com cartão')
        ->assertSee(route('payments.checkout', $appointment));
});

test('client sees the pay button in the appointments list', function () {
    $client = User::factory()->client()->create();
    $appointment = uiAppointmentFor($client);

    $this->actingAs($client)
        ->get(route('appointments.index'))
        ->assertOk()
        ->assertSee(route('payments.checkout', $appointment));
});

test('pay page lists the unpaid appointments of the client', function () {
    $client = User::factory()->client()->create();
    $appointment = uiAppointmentFor($client);
    $someoneElses = uiAppointmentFor(User::factory()->client()->create());

    $this->actingAs($client)
        ->get(route('payments.create'))
        ->assertOk()
        ->assertSee(route('payments.checkout', $appointment))
        ->assertDontSee(route('payments.checkout', $someoneElses));
});

test('there is no pay button when the appointment is already paid', function () {
    $client = User::factory()->client()->create();
    $appointment = uiAppointmentFor($client);

    Payment::create([
        'appointment_id' => $appointment->id,
        'payer_id'       => $client->id,
        'amount'         => 25,
        'method'         => 'card',
        'status'         => PaymentStatus::Paid->value,
    ]);

    $this->actingAs($client)
        ->get(route('appointments.show', $appointment))
        ->assertOk()
        ->assertDontSee('Pagar com cartão')
        ->assertSee('já está paga');

    $this->actingAs($client)
        ->get(route('payments.create'))
        ->assertDontSee(route('payments.checkout', $appointment));
});

test('there is no pay button when the appointment is cancelled', function () {
    $client = User::factory()->client()->create();
    $appointment = uiAppointmentFor($client);
    $appointment->update(['status' => Status::Cancelled]);

    $this->actingAs($client)
        ->get(route('appointments.show', $appointment))
        ->assertOk()
        ->assertDontSee('Pagar com cartão');
});

test('barber does not see the pay button', function () {
    $client = User::factory()->client()->create();
    $appointment = uiAppointmentFor($client);

    $this->actingAs($appointment->barber)
        ->get(route('appointments.show', $appointment))
        ->assertOk()
        ->assertDontSee('Pagar com cartão');
});

test('error messages are shown on the payments page', function () {
    $client = User::factory()->client()->create();

    $this->actingAs($client)
        ->withSession(['error' => 'O pagamento foi cancelado.'])
        ->get(route('payments.index'))
        ->assertSee('O pagamento foi cancelado.');
});

test('admin still sees the manual payment form', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('payments.create'))
        ->assertOk()
        ->assertSee('Criar pagamento')
        ->assertDontSee('Pagar com cartão');
});
