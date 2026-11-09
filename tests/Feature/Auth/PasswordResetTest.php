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

    $this->post(route('password.email'), [
        'email' => $user->email,
    ])
        ->assertRedirect()
        ->assertSessionHas('success');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset link request for an unknown email does not reveal that', function () {
    Notification::fake();

    $this->post(route('password.email'), [
        'email' => 'nobody@example.com',
    ])->assertSessionHas('success');

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

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])
        ->assertRedirect(route('login'))
        ->assertSessionHas('success');

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'new-password',
    ])->assertRedirect(route($user->homeRoute()));
});

test('user cannot reset password with an invalid token', function () {
    $user = User::factory()->client()->create([
        'password' => 'old-password',
    ]);

    $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasErrors('email');

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'new-password',
    ])->assertSessionHasErrors('email');
});
