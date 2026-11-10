<?php

use App\Enums\Status;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;

function listingAppointment(User $client, User $barber, array $extra = []): Appointment
{
    return Appointment::create(array_merge([
        'date'        => now()->addDay()->toDateString(),
        'time'        => '10:00',
        'customer_id' => $client->id,
        'service_id'  => Service::factory()->create()->id,
        'barber_id'   => $barber->id,
    ], $extra));
}

test('admin can search appointments by customer name', function () {
    $admin  = User::factory()->admin()->create();
    $barber = User::factory()->barber()->create();
    $ana    = User::factory()->client()->create(['name' => 'Ana Silvestre']);
    $bruno  = User::factory()->client()->create(['name' => 'Bruno Carvalhoso']);

    listingAppointment($ana, $barber);
    listingAppointment($bruno, $barber);

    $this->actingAs($admin)
        ->get(route('appointments.index', ['q' => 'Silvestre']))
        ->assertOk()
        ->assertSee('Ana Silvestre')
        ->assertDontSee('Bruno Carvalhoso');
});

test('appointments can be filtered by status', function () {
    $admin  = User::factory()->admin()->create();
    $barber = User::factory()->barber()->create();
    $ana    = User::factory()->client()->create(['name' => 'Ana Silvestre']);
    $bruno  = User::factory()->client()->create(['name' => 'Bruno Carvalhoso']);

    listingAppointment($ana, $barber, ['status' => Status::Confirmed]);
    listingAppointment($bruno, $barber, ['status' => Status::Pending]);

    $this->actingAs($admin)
        ->get(route('appointments.index', ['estado' => Status::Confirmed->value]))
        ->assertSee('Ana Silvestre')
        ->assertDontSee('Bruno Carvalhoso');
});

test('appointments can be filtered by barber', function () {
    $admin   = User::factory()->admin()->create();
    $barberA = User::factory()->barber()->create();
    $barberB = User::factory()->barber()->create();
    $ana     = User::factory()->client()->create(['name' => 'Ana Silvestre']);
    $bruno   = User::factory()->client()->create(['name' => 'Bruno Carvalhoso']);

    listingAppointment($ana, $barberA);
    listingAppointment($bruno, $barberB);

    $this->actingAs($admin)
        ->get(route('appointments.index', ['barbeiro' => $barberA->id]))
        ->assertSee('Ana Silvestre')
        ->assertDontSee('Bruno Carvalhoso');
});

test('an unknown status or ordering is ignored instead of breaking the page', function () {
    $admin  = User::factory()->admin()->create();
    $barber = User::factory()->barber()->create();
    $ana    = User::factory()->client()->create(['name' => 'Ana Silvestre']);

    listingAppointment($ana, $barber);

    $this->actingAs($admin)
        ->get(route('appointments.index', ['estado' => 'inventado', 'ordem' => 'id; DROP TABLE users']))
        ->assertOk()
        ->assertSee('Ana Silvestre');

    expect(User::count())->toBeGreaterThan(0);
});

test('a client searching still only sees their own appointments', function () {
    $barber = User::factory()->barber()->create();
    $ana    = User::factory()->client()->create(['name' => 'Ana Silvestre']);
    $bruno  = User::factory()->client()->create(['name' => 'Bruno Silvestre']);

    listingAppointment($ana, $barber);
    listingAppointment($bruno, $barber);

    $this->actingAs($ana)
        ->get(route('appointments.index', ['q' => 'Silvestre']))
        ->assertSee('Ana Silvestre')
        ->assertDontSee('Bruno Silvestre');
});

test('pagination keeps the active filters in the page links', function () {
    $admin  = User::factory()->admin()->create();
    $barber = User::factory()->barber()->create();
    $client = User::factory()->client()->create();

    foreach (range(1, 20) as $i) {
        listingAppointment($client, $barber, ['status' => Status::Pending]);
    }

    $this->actingAs($admin)
        ->get(route('appointments.index', ['estado' => Status::Pending->value]))
        ->assertOk()
        ->assertSee('Página 1 de 2')
        ->assertSee('estado=pending&page=2');
});

test('buttons are hidden when the policy does not allow the action', function () {
    $barber = User::factory()->barber()->create();
    $client = User::factory()->client()->create();

    $completed = listingAppointment($client, $barber, ['status' => Status::Completed]);
    $pending   = listingAppointment($client, $barber, ['status' => Status::Pending]);

    $response = $this->actingAs($client)->get(route('appointments.index'));

    // Concluída: o cliente já não a pode editar. Pendente: pode.
    $response->assertDontSee(route('appointments.edit', $completed))
        ->assertSee(route('appointments.edit', $pending));
});

test('the url typed by hand is still refused even when the button is hidden', function () {
    $barber = User::factory()->barber()->create();
    $client = User::factory()->client()->create();

    $completed = listingAppointment($client, $barber, ['status' => Status::Completed]);

    $this->actingAs($client)
        ->get(route('appointments.edit', $completed))
        ->assertForbidden();
});
