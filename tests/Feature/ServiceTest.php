<?php

use App\Models\Service;
use App\Models\User;

test('everyone can see the services list', function () {
    Service::factory()->create();

    $this->get(route('services.index'))->assertOk();
});

test('guest does not see the service management buttons', function () {
    Service::factory()->create();

    $this->get(route('services.index'))
        ->assertOk()
        ->assertDontSee('Novo serviço')
        ->assertDontSee('Eliminar');
});

test('only the admin sees the service management buttons', function () {
    Service::factory()->create();

    $this->actingAs(User::factory()->client()->create())
        ->get(route('services.index'))
        ->assertDontSee('Novo serviço')
        ->assertDontSee('Eliminar');

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('services.index'))
        ->assertSee('Novo serviço')
        ->assertSee('Eliminar');
});

test('client and barber cannot open the service edit page', function () {
    $service = Service::factory()->create();

    foreach ([User::factory()->client()->create(), User::factory()->barber()->create()] as $user) {
        $this->actingAs($user)
            ->get(route('services.edit', $service))
            ->assertForbidden();
    }
});

test('client cannot delete a service', function () {
    $service = Service::factory()->create();

    $this->actingAs(User::factory()->client()->create())
        ->delete(route('services.destroy', $service))
        ->assertForbidden();

    $this->assertDatabaseHas('services', ['id' => $service->id]);
});

test('admin can delete a service', function () {
    $service = Service::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('services.destroy', $service))
        ->assertRedirect(route('services.index'));

    $this->assertDatabaseMissing('services', ['id' => $service->id]);
});

test('service policy refuses client and barber, admin passes', function () {
    $service = Service::factory()->create();
    $client = User::factory()->client()->create();
    $barber = User::factory()->barber()->create();
    $admin = User::factory()->admin()->create();

    expect($client->can('create', Service::class))->toBeFalse();
    expect($barber->can('update', $service))->toBeFalse();
    expect($client->can('delete', $service))->toBeFalse();
    expect($admin->can('create', Service::class))->toBeTrue();
    expect($admin->can('update', $service))->toBeTrue();
});
