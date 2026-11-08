<?php

use App\Models\Review;
use App\Models\Service;
use App\Models\User;

test('guest is redirected to login when listing reviews', function () {
    $this->get(route('reviews.index'))
        ->assertRedirect(route('login'));
});

function completedAppointmentFor(User $client): \App\Models\Appointment
{
    return \App\Models\Appointment::create([
        'date' => now()->subDay()->toDateString(),
        'time' => '10:00',
        'status' => \App\Enums\Status::Completed,
        'customer_id' => $client->id,
        'service_id' => Service::factory()->create()->id,
        'barber_id' => User::factory()->barber()->create()->id,
    ]);
}

test('client reviews barber and service separately for a completed appointment', function () {
    $client = User::factory()->client()->create();
    $impersonated = User::factory()->client()->create();
    $appointment = completedAppointmentFor($client);

    $this->actingAs($client)->post(route('reviews.store'), [
        // reviewer_id spoofado deliberadamente: deve ser ignorado.
        'reviewer_id' => $impersonated->id,
        'appointment_id' => $appointment->id,
        'barber_rating' => 5,
        'barber_comment' => 'Excelente barbeiro.',
        'service_rating' => 4,
        'service_comment' => 'Bom corte.',
    ])->assertRedirect(route('reviews.index'));

    $this->assertDatabaseHas('reviews', [
        'reviewer_id' => $client->id,
        'appointment_id' => $appointment->id,
        'reviewable_type' => User::class,
        'reviewable_id' => $appointment->barber_id,
        'rating' => 5,
    ]);
    $this->assertDatabaseHas('reviews', [
        'reviewer_id' => $client->id,
        'appointment_id' => $appointment->id,
        'reviewable_type' => Service::class,
        'reviewable_id' => $appointment->service_id,
        'rating' => 4,
    ]);
    $this->assertDatabaseMissing('reviews', ['reviewer_id' => $impersonated->id]);
});

test('client cannot review the same appointment twice', function () {
    $client = User::factory()->client()->create();
    $appointment = completedAppointmentFor($client);
    $payload = [
        'appointment_id' => $appointment->id,
        'barber_rating' => 5,
        'service_rating' => 5,
    ];

    $this->actingAs($client)->post(route('reviews.store'), $payload);
    $this->actingAs($client)->post(route('reviews.store'), $payload)
        ->assertSessionHasErrors('appointment_id');

    expect(Review::count())->toBe(2);
});

test('client cannot review an appointment of another client or not completed', function () {
    $client = User::factory()->client()->create();
    $other = User::factory()->client()->create();
    $foreign = completedAppointmentFor($other);

    $this->actingAs($client)->post(route('reviews.store'), [
        'appointment_id' => $foreign->id,
        'barber_rating' => 5,
        'service_rating' => 5,
    ])->assertSessionHasErrors('appointment_id');

    $pending = completedAppointmentFor($client);
    $pending->update(['status' => \App\Enums\Status::Pending]);

    $this->actingAs($client)->post(route('reviews.store'), [
        'appointment_id' => $pending->id,
        'barber_rating' => 5,
        'service_rating' => 5,
    ])->assertSessionHasErrors('appointment_id');

    expect(Review::count())->toBe(0);
});

test('create form lists only own completed appointments not yet reviewed', function () {
    $client = User::factory()->client()->create();
    $mine = completedAppointmentFor($client);
    completedAppointmentFor(User::factory()->client()->create());

    $this->actingAs($client)->get(route('reviews.create'))
        ->assertOk()
        ->assertViewHas('appointments', fn ($a) => $a->pluck('id')->all() === [$mine->id]);
});

test('user can update their own review', function () {
    $client = User::factory()->client()->create();
    $service = Service::factory()->create();

    $review = Review::create([
        'reviewer_id' => $client->id,
        'reviewable_type' => Service::class,
        'reviewable_id' => $service->id,
        'rating' => 3,
        'comment' => 'Razoável.',
    ]);

    $response = $this->actingAs($client)->put(route('reviews.update', $review), [
        'rating' => 5,
        'comment' => 'Afinal adorei.',
    ]);

    $response->assertRedirect(route('reviews.index'));

    $this->assertDatabaseHas('reviews', [
        'id' => $review->id,
        'reviewer_id' => $client->id,
        'rating' => 5,
    ]);
});

test('user cannot edit another user review', function () {
    $author = User::factory()->client()->create();
    $intruder = User::factory()->client()->create();
    $service = Service::factory()->create();

    $review = Review::create([
        'reviewer_id' => $author->id,
        'reviewable_type' => Service::class,
        'reviewable_id' => $service->id,
        'rating' => 4,
        'comment' => 'Bom.',
    ]);

    $this->actingAs($intruder)
        ->put(route('reviews.update', $review), [
                'rating' => 1,
            'comment' => 'Sabotagem.',
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('reviews', [
        'id' => $review->id,
        'rating' => 4,
    ]);
});

test('user cannot delete another user review', function () {
    $author = User::factory()->client()->create();
    $intruder = User::factory()->client()->create();
    $service = Service::factory()->create();

    $review = Review::create([
        'reviewer_id' => $author->id,
        'reviewable_type' => Service::class,
        'reviewable_id' => $service->id,
        'rating' => 4,
        'comment' => 'Bom.',
    ]);

    $this->actingAs($intruder)
        ->delete(route('reviews.destroy', $review))
        ->assertForbidden();

    $this->assertDatabaseHas('reviews', ['id' => $review->id]);
});

test('admin can delete any review', function () {
    $admin = User::factory()->admin()->create();
    $author = User::factory()->client()->create();
    $service = Service::factory()->create();

    $review = Review::create([
        'reviewer_id' => $author->id,
        'reviewable_type' => Service::class,
        'reviewable_id' => $service->id,
        'rating' => 2,
        'comment' => 'Fraco.',
    ]);

    $this->actingAs($admin)
        ->delete(route('reviews.destroy', $review))
        ->assertRedirect(route('reviews.index'));

    $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
});
