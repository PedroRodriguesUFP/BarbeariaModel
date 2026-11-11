<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function validRegistration(array $overrides = []): array
{
    return array_merge([
        'name' => 'Maria Costa',
        'email' => 'maria@example.com',
        'phone' => '912345678',
        'password' => 'palavra-passe-segura',
        'password_confirmation' => 'palavra-passe-segura',
    ], $overrides);
}

test('registration page is shown to guests', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Criar conta');
});

test('visitor can register and becomes a client who is logged in', function () {
    $this->post(route('register'), validRegistration())
        ->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'maria@example.com')->firstOrFail();

    expect($user->role)->toBe(UserRole::Client)
        ->and($user->active)->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

test('registration ignores a role sent in the form', function () {
    $this->post(route('register'), validRegistration(['role' => 'admin', 'active' => 0]));

    $user = User::where('email', 'maria@example.com')->firstOrFail();

    expect($user->role)->toBe(UserRole::Client)
        ->and($user->active)->toBeTrue();
});

test('registration stores the password hashed', function () {
    $this->post(route('register'), validRegistration());

    $user = User::where('email', 'maria@example.com')->firstOrFail();

    expect($user->password)->not->toBe('palavra-passe-segura')
        ->and(Hash::check('palavra-passe-segura', $user->password))->toBeTrue();
});

test('registration rejects an email that already exists', function () {
    User::factory()->client()->create(['email' => 'maria@example.com']);

    $this->post(route('register'), validRegistration())
        ->assertSessionHasErrors('email');

    expect(User::where('email', 'maria@example.com')->count())->toBe(1);
});

test('registration rejects a wrong password confirmation', function () {
    $this->post(route('register'), validRegistration(['password_confirmation' => 'outra-coisa']))
        ->assertSessionHasErrors('password');

    $this->assertGuest();
});

test('registration rejects a short password', function () {
    $this->post(route('register'), validRegistration([
        'password' => 'curta',
        'password_confirmation' => 'curta',
    ]))->assertSessionHasErrors('password');
});

test('a logged in user is sent away from the registration page', function () {
    $client = User::factory()->client()->create();

    $this->actingAs($client)
        ->get(route('register'))
        ->assertRedirect(route('appointments.index'));
});

test('login page links to the registration page', function () {
    $this->get(route('login'))->assertSee(route('register'));
});
