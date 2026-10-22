<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('forgot password form is visible', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Recuperar password');
});

test('sending a reset link notifies the user with a token', function () {
    Notification::fake();

    $user = User::factory()->client()->create();

    $response = $this->post(route('password.email'), [
        'email' => $user->email,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset link request for an unknown email does not reveal that', function () {
    Notification::fake();

    $response = $this->post(route('password.email'), [
        'email' => 'nobody@example.com',
    ]);

    // A app não deve distinguir entre email existente e inexistente.
    $response->assertSessionHas('success');
    Notification::assertNothingSent();
});

test('reset password form is visible with a token', function () {
    $user = User::factory()->client()->create();

    $this->get(route('password.reset', [
        'token' => 'any-token',
        'email' => $user->email,
    ]))
        ->assertOk()
        ->assertSee('Repor password');
});

test('user can reset password with a valid token', function () {
    $user = User::factory()->client()->create([
        'password' => 'old-password',
    ]);

    $token = Password::createToken($user);

    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('success');

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'new-password',
    ])->assertRedirect(route('home'));
});

test('user cannot reset password with an invalid token', function () {
    $user = User::factory()->client()->create([
        'password' => 'old-password',
    ]);

    $response = $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasErrors('email');

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'new-password',
    ])->assertSessionHasErrors('email');
});
