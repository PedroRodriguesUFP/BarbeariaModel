<?php

use App\Models\ApiToken;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

// A disponibilidade consulta o serviço externo de feriados: nos testes nunca vai à rede.
beforeEach(function () {
    Cache::flush();
    Http::fake(['date.nager.at/*' => Http::response([], 200)]);
});

function apiBarberFor(Service $service): User
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

function apiNextMonday(): string
{
    return now()->next('Monday')->toDateString();
}

test('cliente cria marcação via API (201)', function () {
    $client = User::factory()->client()->create();
    $service = Service::factory()->create(['duration_minutes' => 30]);
    $barber = apiBarberFor($service);

    $this->withToken(ApiToken::issueFor($client, 't'))
        ->postJson('/api/v1/appointments', [
            'date' => apiNextMonday(),
            'time' => '10:00',
            'barber_id' => $barber->id,
            'service_id' => $service->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.barber.id', $barber->id);

    $this->assertDatabaseHas('appointments', ['customer_id' => $client->id, 'barber_id' => $barber->id]);
});

test('marcação em horário ocupado devolve 422', function () {
    $client = User::factory()->client()->create();
    $other = User::factory()->client()->create();
    $service = Service::factory()->create(['duration_minutes' => 30]);
    $barber = apiBarberFor($service);

    Appointment::create([
        'date' => apiNextMonday(), 'time' => '10:00',
        'customer_id' => $other->id, 'service_id' => $service->id, 'barber_id' => $barber->id,
    ]);

    $this->withToken(ApiToken::issueFor($client, 't'))
        ->postJson('/api/v1/appointments', [
            'date' => apiNextMonday(), 'time' => '10:00',
            'barber_id' => $barber->id, 'service_id' => $service->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('time');
});

test('barbeiro não pode criar marcação como cliente (403)', function () {
    $service = Service::factory()->create();
    $barber = apiBarberFor($service);
    $other = apiBarberFor($service);

    $this->withToken(ApiToken::issueFor($barber, 't'))
        ->postJson('/api/v1/appointments', [
            'date' => apiNextMonday(), 'time' => '10:00',
            'barber_id' => $other->id, 'service_id' => $service->id,
        ])->assertForbidden();
});

test('cliente só lista as suas marcações', function () {
    $mine = User::factory()->client()->create();
    $other = User::factory()->client()->create();
    $service = Service::factory()->create();
    $barber = apiBarberFor($service);

    $a = Appointment::create(['date' => apiNextMonday(), 'time' => '10:00', 'customer_id' => $mine->id, 'service_id' => $service->id, 'barber_id' => $barber->id]);
    Appointment::create(['date' => apiNextMonday(), 'time' => '11:00', 'customer_id' => $other->id, 'service_id' => $service->id, 'barber_id' => $barber->id]);

    $this->withToken(ApiToken::issueFor($mine, 't'))
        ->getJson('/api/v1/appointments')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $a->id);
});

test('cliente não vê marcação de outro (403)', function () {
    $mine = User::factory()->client()->create();
    $other = User::factory()->client()->create();
    $service = Service::factory()->create();
    $barber = apiBarberFor($service);

    $theirs = Appointment::create(['date' => apiNextMonday(), 'time' => '11:00', 'customer_id' => $other->id, 'service_id' => $service->id, 'barber_id' => $barber->id]);

    $this->withToken(ApiToken::issueFor($mine, 't'))
        ->getJson("/api/v1/appointments/{$theirs->id}")
        ->assertForbidden();
});

test('cliente altera e cancela (apaga) a sua marcação', function () {
    $client = User::factory()->client()->create();
    $service = Service::factory()->create(['duration_minutes' => 30]);
    $barber = apiBarberFor($service);
    $token = ApiToken::issueFor($client, 't');

    $appointment = Appointment::create(['date' => apiNextMonday(), 'time' => '10:00', 'customer_id' => $client->id, 'service_id' => $service->id, 'barber_id' => $barber->id]);

    $this->withToken($token)->putJson("/api/v1/appointments/{$appointment->id}", [
        'date' => apiNextMonday(), 'time' => '14:00',
        'barber_id' => $barber->id, 'service_id' => $service->id,
    ])->assertOk()->assertJsonPath('data.time', '14:00');

    $this->withToken($token)->deleteJson("/api/v1/appointments/{$appointment->id}")->assertNoContent();
    $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
});

// Só passa depois de aplicar o zip dos feriados (HolidayService + User::isAvailableFor).
test('marcação num feriado devolve 422', function () {
    Http::fake(['date.nager.at/*' => Http::response([
        ['date' => apiNextMonday(), 'name' => 'Feriado de teste'],
    ], 200)]);

    $client = User::factory()->client()->create();
    $service = Service::factory()->create(['duration_minutes' => 30]);
    $barber = apiBarberFor($service);

    $this->withToken(ApiToken::issueFor($client, 't'))
        ->postJson('/api/v1/appointments', [
            'date' => apiNextMonday(), 'time' => '10:00',
            'barber_id' => $barber->id, 'service_id' => $service->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('time');
});
