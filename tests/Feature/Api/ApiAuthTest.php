<?php

use App\Models\ApiToken;
use App\Models\User;

test('login na API devolve token Bearer', function () {
    $user = User::factory()->client()->create();

    $response = $this->postJson('/api/v1/auth/token', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'teste',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'name', 'role']]);

    // Só o hash fica guardado.
    $this->assertDatabaseMissing('api_tokens', ['token' => $response->json('token')]);
    $this->assertDatabaseCount('api_tokens', 1);
});

test('login na API com password errada devolve 422', function () {
    $user = User::factory()->client()->create();

    $this->postJson('/api/v1/auth/token', [
        'email' => $user->email,
        'password' => 'errada',
        'device_name' => 'teste',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('conta desativada não obtém token', function () {
    $user = User::factory()->client()->inactive()->create();

    $this->postJson('/api/v1/auth/token', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'teste',
    ])->assertUnprocessable();
});

test('pedido sem token devolve 401', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
    $this->getJson('/api/v1/appointments')->assertUnauthorized();
});

test('token inválido devolve 401', function () {
    $this->withToken('nao-existe')->getJson('/api/v1/me')->assertUnauthorized();
});

test('token expirado devolve 401', function () {
    $user = User::factory()->client()->create();
    $plain = ApiToken::issueFor($user, 'teste', days: 1);
    ApiToken::query()->update(['expires_at' => now()->subMinute()]);

    $this->withToken($plain)->getJson('/api/v1/me')->assertUnauthorized();
});

test('token válido devolve o utilizador autenticado', function () {
    $user = User::factory()->client()->create();
    $plain = ApiToken::issueFor($user, 'teste');

    $this->withToken($plain)->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

test('logout da API revoga o token', function () {
    $user = User::factory()->client()->create();
    $plain = ApiToken::issueFor($user, 'teste');

    $this->withToken($plain)->deleteJson('/api/v1/auth/token')->assertNoContent();

    $this->withToken($plain)->getJson('/api/v1/me')->assertUnauthorized();
});
