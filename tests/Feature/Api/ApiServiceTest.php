<?php

use App\Models\ApiToken;
use App\Models\Service;
use App\Models\User;

function tokenFor(User $user): string
{
    return ApiToken::issueFor($user, 'teste');
}

test('cliente lista serviços com paginação', function () {
    Service::factory()->count(3)->create();
    $token = tokenFor(User::factory()->client()->create());

    $this->withToken($token)->getJson('/api/v1/services')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'name', 'price', 'duration_minutes', 'category']], 'links', 'meta']);
});

test('cliente vê um serviço e 404 se não existir', function () {
    $service = Service::factory()->create();
    $token = tokenFor(User::factory()->client()->create());

    $this->withToken($token)->getJson("/api/v1/services/{$service->id}")
        ->assertOk()->assertJsonPath('data.id', $service->id);

    $this->withToken($token)->getJson('/api/v1/services/9999')->assertNotFound();
});

test('admin cria serviço e recebe 201', function () {
    $token = tokenFor(User::factory()->admin()->create());

    $this->withToken($token)->postJson('/api/v1/services', [
        'name' => 'Corte clássico',
        'price' => 15.5,
        'duration_minutes' => 30,
        'category' => 'hair',
    ])->assertCreated()->assertJsonPath('data.name', 'Corte clássico');

    $this->assertDatabaseHas('services', ['name' => 'Corte clássico']);
});

test('criar serviço com dados inválidos devolve 422', function () {
    $token = tokenFor(User::factory()->admin()->create());

    $this->withToken($token)->postJson('/api/v1/services', [
        'name' => '',
        'price' => -1,
        'duration_minutes' => 0,
        'category' => 'inexistente',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'price', 'duration_minutes', 'category']);
});

test('cliente não pode criar, alterar nem apagar serviços (403)', function () {
    $service = Service::factory()->create();
    $token = tokenFor(User::factory()->client()->create());

    $payload = ['name' => 'X', 'price' => 10, 'duration_minutes' => 20, 'category' => 'hair'];

    $this->withToken($token)->postJson('/api/v1/services', $payload)->assertForbidden();
    $this->withToken($token)->putJson("/api/v1/services/{$service->id}", $payload)->assertForbidden();
    $this->withToken($token)->deleteJson("/api/v1/services/{$service->id}")->assertForbidden();
});

test('admin altera e apaga serviço', function () {
    $service = Service::factory()->create();
    $token = tokenFor(User::factory()->admin()->create());

    $this->withToken($token)->putJson("/api/v1/services/{$service->id}", [
        'name' => 'Novo nome',
        'price' => 20,
        'duration_minutes' => 45,
        'category' => 'beard',
    ])->assertOk()->assertJsonPath('data.name', 'Novo nome');

    $this->withToken($token)->deleteJson("/api/v1/services/{$service->id}")->assertNoContent();
    $this->assertDatabaseMissing('services', ['id' => $service->id]);
});
