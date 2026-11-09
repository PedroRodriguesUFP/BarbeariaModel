<?php

use App\Models\User;

test('login form is visible to guests', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Entrar');
});

test('user can login with correct credentials', function () {
    $user = User::factory()->client()->create([
        'password' => 'password',
    ]);

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route($user->homeRoute()));
    $this->assertAuthenticatedAs($user);
});

test('dashboard redirects to the users home route', function () {
    $user = User::factory()->client()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route($user->homeRoute()));
});

test('user cannot login with wrong password', function () {
    $user = User::factory()->client()->create([
        'password' => 'password',
    ]);

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors([
        'email' => 'Email ou palavra-passe incorretos.',
    ]);
    $this->assertGuest();
});

test('inactive user cannot login even with correct credentials', function () {
    $user = User::factory()->client()->inactive()->create([
        'password' => 'password',
    ]);

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors([
        'email' => 'Email ou palavra-passe incorretos.',
    ]);
    $this->assertGuest();
});

test('authenticated user can logout', function () {
    $user = User::factory()->client()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));
    $this->assertGuest();
});
