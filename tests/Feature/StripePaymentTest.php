<?php

use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Services\StripeGateway;
use Stripe\Checkout\Session;
use Stripe\Event;

// ----------------------------------------------------------------------
// Auxiliares. Nenhum teste fala com a Stripe a sério: o StripeGateway
// é trocado por um falso e as sessões/eventos são construídos à mão.
// ----------------------------------------------------------------------

function stripeAppointmentFor(?User $customer, float $price = 25): Appointment
{
    return Appointment::create([
        'date'        => now()->addDay()->toDateString(),
        'time'        => '10:00',
        'customer_id' => $customer?->id,
        'service_id'  => Service::factory()->create(['price' => $price])->id,
        'barber_id'   => User::factory()->barber()->create()->id,
    ]);
}

function fakeStripeSession(Appointment $appointment, string $id = 'cs_test_1', string $paymentStatus = 'paid', ?int $amountCents = null): Session
{
    return Session::constructFrom([
        'id'                  => $id,
        'payment_status'      => $paymentStatus,
        'client_reference_id' => (string) $appointment->id,
        'amount_total'        => $amountCents ?? (int) round($appointment->service->price * 100),
    ]);
}

function fakeStripeEvent(string $type, Session $session): Event
{
    return Event::constructFrom([
        'id'   => 'evt_test_1',
        'type' => $type,
        'data' => ['object' => $session->toArray()],
    ]);
}

// ----------------------------------------------------------------------
// checkout
// ----------------------------------------------------------------------

test('checkout redirects the client to the stripe page', function () {
    $client = User::factory()->client()->create();
    $appointment = stripeAppointmentFor($client);

    $this->mock(StripeGateway::class)
        ->shouldReceive('createCheckoutSession')
        ->once()
        ->andReturn(Session::constructFrom(['url' => 'https://checkout.stripe.test/pay']));

    $this->actingAs($client)
        ->post(route('payments.checkout', $appointment))
        ->assertRedirect('https://checkout.stripe.test/pay');
});

test('checkout is forbidden for an appointment of another client', function () {
    $client = User::factory()->client()->create();
    $appointment = stripeAppointmentFor(User::factory()->client()->create());

    $this->mock(StripeGateway::class)->shouldNotReceive('createCheckoutSession');

    $this->actingAs($client)
        ->post(route('payments.checkout', $appointment))
        ->assertForbidden();
});

test('checkout refuses an appointment that is already paid', function () {
    $client = User::factory()->client()->create();
    $appointment = stripeAppointmentFor($client);

    Payment::create([
        'appointment_id' => $appointment->id,
        'payer_id'       => $client->id,
        'amount'         => 25,
        'method'         => 'card',
        'status'         => PaymentStatus::Paid->value,
    ]);

    $this->mock(StripeGateway::class)->shouldNotReceive('createCheckoutSession');

    $this->actingAs($client)
        ->post(route('payments.checkout', $appointment))
        ->assertSessionHas('error');
});

test('checkout shows a friendly error when stripe is down', function () {
    $client = User::factory()->client()->create();
    $appointment = stripeAppointmentFor($client);

    $this->mock(StripeGateway::class)
        ->shouldReceive('createCheckoutSession')
        ->andThrow(new RuntimeException('stripe down'));

    $this->actingAs($client)
        ->post(route('payments.checkout', $appointment))
        ->assertSessionHas('error', 'Serviço de pagamento indisponível.');
});

// ----------------------------------------------------------------------
// success (o cliente volta da Stripe)
// ----------------------------------------------------------------------

test('success records the payment using the amount charged by stripe', function () {
    $client = User::factory()->client()->create();
    $appointment = stripeAppointmentFor($client, 25);

    $this->mock(StripeGateway::class)
        ->shouldReceive('retrieveSession')
        ->with('cs_test_1')
        ->andReturn(fakeStripeSession($appointment, 'cs_test_1', 'paid', 2500));

    $this->actingAs($client)
        ->get(route('payments.success', ['appointment' => $appointment->id, 'session_id' => 'cs_test_1']))
        ->assertRedirect(route('payments.index'))
        ->assertSessionHas('success');

    $payment = Payment::where('appointment_id', $appointment->id)->sole();

    expect($payment->payer_id)->toEqual($client->id)
        ->and($payment->status)->toBe('paid')
        ->and($payment->method)->toBe('card')
        ->and($payment->stripe_session_id)->toBe('cs_test_1')
        ->and((float) $payment->amount)->toBe(25.0);
});

test('reloading the success page does not create a second payment', function () {
    $client = User::factory()->client()->create();
    $appointment = stripeAppointmentFor($client);

    $this->mock(StripeGateway::class)
        ->shouldReceive('retrieveSession')
        ->andReturn(fakeStripeSession($appointment));

    $url = route('payments.success', ['appointment' => $appointment->id, 'session_id' => 'cs_test_1']);

    $this->actingAs($client)->get($url);
    $this->actingAs($client)->get($url)->assertSessionHas('success');

    expect(Payment::where('appointment_id', $appointment->id)->count())->toBe(1);
});

test('success does not record a session that is not paid', function () {
    $client = User::factory()->client()->create();
    $appointment = stripeAppointmentFor($client);

    $this->mock(StripeGateway::class)
        ->shouldReceive('retrieveSession')
        ->andReturn(fakeStripeSession($appointment, 'cs_test_1', 'unpaid'));

    $this->actingAs($client)
        ->get(route('payments.success', ['appointment' => $appointment->id, 'session_id' => 'cs_test_1']))
        ->assertSessionHas('error');

    expect(Payment::count())->toBe(0);
});

test('success rejects a session that belongs to another appointment', function () {
    $client = User::factory()->client()->create();
    $mine = stripeAppointmentFor($client);
    $other = stripeAppointmentFor($client);

    // Sessão paga, mas da outra marcação.
    $this->mock(StripeGateway::class)
        ->shouldReceive('retrieveSession')
        ->andReturn(fakeStripeSession($other));

    $this->actingAs($client)
        ->get(route('payments.success', ['appointment' => $mine->id, 'session_id' => 'cs_test_1']))
        ->assertSessionHas('error');

    expect(Payment::count())->toBe(0);
});

test('success is forbidden for an appointment of another client', function () {
    $client = User::factory()->client()->create();
    $appointment = stripeAppointmentFor(User::factory()->client()->create());

    $this->mock(StripeGateway::class)->shouldNotReceive('retrieveSession');

    $this->actingAs($client)
        ->get(route('payments.success', ['appointment' => $appointment->id, 'session_id' => 'cs_test_1']))
        ->assertForbidden();
});

test('success without a session id records nothing', function () {
    $client = User::factory()->client()->create();
    $appointment = stripeAppointmentFor($client);

    $this->mock(StripeGateway::class)->shouldNotReceive('retrieveSession');

    $this->actingAs($client)
        ->get(route('payments.success', ['appointment' => $appointment->id]))
        ->assertSessionHas('error');

    expect(Payment::count())->toBe(0);
});

// ----------------------------------------------------------------------
// webhook
// ----------------------------------------------------------------------

test('webhook with an invalid signature is rejected', function () {
    $this->mock(StripeGateway::class)
        ->shouldReceive('constructWebhookEvent')
        ->andThrow(new UnexpectedValueException('bad signature'));

    $this->postJson('/api/stripe/webhook', ['any' => 'thing'])
        ->assertStatus(400);

    expect(Payment::count())->toBe(0);
});

test('webhook validates the real stripe signature', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test']);

    $client = User::factory()->client()->create();
    $appointment = stripeAppointmentFor($client);

    $payload = json_encode(fakeStripeEvent(
        'checkout.session.completed',
        fakeStripeSession($appointment)
    )->toArray());

    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp . '.' . $payload, 'whsec_test');

    $this->call('POST', '/api/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        'CONTENT_TYPE'          => 'application/json',
    ], $payload)->assertOk();

    expect(Payment::where('appointment_id', $appointment->id)->count())->toBe(1);

    // Assinatura errada: recusado e nada novo gravado.
    $this->call('POST', '/api/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1=falsa",
        'CONTENT_TYPE'          => 'application/json',
    ], $payload)->assertStatus(400);
});

test('webhook records a paid checkout session', function () {
    $client = User::factory()->client()->create();
    $appointment = stripeAppointmentFor($client, 30);

    $this->mock(StripeGateway::class)
        ->shouldReceive('constructWebhookEvent')
        ->andReturn(fakeStripeEvent('checkout.session.completed', fakeStripeSession($appointment, 'cs_test_9', 'paid', 3000)));

    $this->postJson('/api/stripe/webhook', [])->assertOk();

    $payment = Payment::where('appointment_id', $appointment->id)->sole();

    expect($payment->payer_id)->toEqual($client->id)
        ->and($payment->status)->toBe('paid')
        ->and((float) $payment->amount)->toBe(30.0);
});

test('webhook after success does not duplicate the payment', function () {
    $client = User::factory()->client()->create();
    $appointment = stripeAppointmentFor($client);
    $session = fakeStripeSession($appointment, 'cs_test_1');

    $gateway = $this->mock(StripeGateway::class);
    $gateway->shouldReceive('retrieveSession')->andReturn($session);
    $gateway->shouldReceive('constructWebhookEvent')
        ->andReturn(fakeStripeEvent('checkout.session.completed', $session));

    $this->actingAs($client)
        ->get(route('payments.success', ['appointment' => $appointment->id, 'session_id' => 'cs_test_1']));

    $this->postJson('/api/stripe/webhook', [])->assertOk();
    $this->postJson('/api/stripe/webhook', [])->assertOk(); // a Stripe repete avisos

    expect(Payment::where('appointment_id', $appointment->id)->count())->toBe(1);
});

test('webhook marks the payment even when a pending cash payment already exists', function () {
    $client = User::factory()->client()->create();
    $appointment = stripeAppointmentFor($client);

    Payment::create([
        'appointment_id' => $appointment->id,
        'payer_id'       => $client->id,
        'amount'         => 25,
        'method'         => 'cash',
        'status'         => PaymentStatus::Pending->value,
    ]);

    $this->mock(StripeGateway::class)
        ->shouldReceive('constructWebhookEvent')
        ->andReturn(fakeStripeEvent('checkout.session.completed', fakeStripeSession($appointment)));

    $this->postJson('/api/stripe/webhook', [])->assertOk();

    expect(Payment::where('appointment_id', $appointment->id)->where('status', 'paid')->count())->toBe(1);
});

test('webhook ignores sessions that are not paid', function () {
    $client = User::factory()->client()->create();
    $appointment = stripeAppointmentFor($client);

    $this->mock(StripeGateway::class)
        ->shouldReceive('constructWebhookEvent')
        ->andReturn(fakeStripeEvent('checkout.session.completed', fakeStripeSession($appointment, 'cs_test_1', 'unpaid')));

    $this->postJson('/api/stripe/webhook', [])->assertOk();

    expect(Payment::count())->toBe(0);
});

test('webhook answers 200 for an unknown appointment', function () {
    $appointment = stripeAppointmentFor(User::factory()->client()->create());
    $session = fakeStripeSession($appointment);
    $appointment->delete();

    $this->mock(StripeGateway::class)
        ->shouldReceive('constructWebhookEvent')
        ->andReturn(fakeStripeEvent('checkout.session.completed', $session));

    // Se devolvesse erro, a Stripe repetia o aviso durante dias.
    $this->postJson('/api/stripe/webhook', [])->assertOk();

    expect(Payment::count())->toBe(0);
});

test('webhook answers 200 for a walk-in appointment without account', function () {
    $appointment = stripeAppointmentFor(null);

    $this->mock(StripeGateway::class)
        ->shouldReceive('constructWebhookEvent')
        ->andReturn(fakeStripeEvent('checkout.session.completed', fakeStripeSession($appointment)));

    $this->postJson('/api/stripe/webhook', [])->assertOk();

    expect(Payment::count())->toBe(0);
});

test('webhook ignores other event types', function () {
    $appointment = stripeAppointmentFor(User::factory()->client()->create());

    $this->mock(StripeGateway::class)
        ->shouldReceive('constructWebhookEvent')
        ->andReturn(fakeStripeEvent('charge.refunded', fakeStripeSession($appointment)));

    $this->postJson('/api/stripe/webhook', [])->assertOk();

    expect(Payment::count())->toBe(0);
});
