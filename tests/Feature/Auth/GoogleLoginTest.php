<?php

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

// NOTA: estes testes pressupõem o pacote laravel/socialite instalado
// (composer require laravel/socialite). Sem ele, GoogleLoginController
// nunca chega a carregar a classe Socialite e isto falha com
// "Class not found" antes de qualquer asserção correr.

function fakeGoogleUser(string $id, string $email, string $name): SocialiteUser
{
    $socialiteUser = new SocialiteUser();
    $socialiteUser->id = $id;
    $socialiteUser->email = $email;
    $socialiteUser->name = $name;

    return $socialiteUser;
}

function mockSocialiteReturning(SocialiteUser $user): void
{
    $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('user')->andReturn($user);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
}

test('new google user without an account is created as a client', function () {
    mockSocialiteReturning(
        fakeGoogleUser('google-123', 'novo@example.com', 'Novo Utilizador')
    );

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('home'));

    $this->assertDatabaseHas('users', [
        'email' => 'novo@example.com',
        'google_id' => 'google-123',
        'role' => UserRole::Client->value,
    ]);

    $this->assertAuthenticated();
});

test('existing user is linked to google by matching email', function () {
    $user = User::factory()->client()->create([
        'email' => 'existe@example.com',
        'google_id' => null,
    ]);

    mockSocialiteReturning(
        fakeGoogleUser('google-456', 'existe@example.com', $user->name)
    );

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user->fresh());
    expect($user->fresh()->google_id)->toBe('google-456');
});

test('user already linked by google id logs in directly', function () {
    $user = User::factory()->client()->create([
        'google_id' => 'google-789',
    ]);

    mockSocialiteReturning(
        fakeGoogleUser('google-789', 'outro-email@example.com', $user->name)
    );

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

test('inactive account cannot login through google', function () {
    $user = User::factory()->client()->inactive()->create([
        'google_id' => 'google-999',
    ]);

    mockSocialiteReturning(
        fakeGoogleUser('google-999', $user->email, $user->name)
    );

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});
