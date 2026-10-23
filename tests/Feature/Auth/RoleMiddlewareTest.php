<?php

use App\Models\User;

test('guest is redirected to login when accessing a role protected route', function () {
    $this->get(route('barbers.index'))
        ->assertRedirect(route('login'));
});

test('admin can access an admin only route', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('barbers.index'))
        ->assertOk();
});

test('client is forbidden from an admin only route', function () {
    $client = User::factory()->client()->create();

    $this->actingAs($client)
        ->get(route('barbers.index'))
        ->assertForbidden();
});

test('barber is forbidden from an admin only route', function () {
    $barber = User::factory()->barber()->create();

    $this->actingAs($barber)
        ->get(route('barbers.index'))
        ->assertForbidden();
});

test('barber can access their own calendar route', function () {
    $barber = User::factory()->barber()->create();

    $this->actingAs($barber)
        ->get(route('calendar.mine'))
        ->assertOk();
});

test('client is forbidden from the barber calendar route', function () {
    $client = User::factory()->client()->create();

    $this->actingAs($client)
        ->get(route('calendar.mine'))
        ->assertForbidden();
});
