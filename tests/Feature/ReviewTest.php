<?php

use App\Models\Review;
use App\Models\Service;
use App\Models\User;

test('guest is redirected to login when listing reviews', function () {
    $this->get(route('reviews.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can write a review and reviewer_id is forced server side', function () {
    $client = User::factory()->client()->create();
    $impersonated = User::factory()->client()->create();
    $service = Service::factory()->create();

    $response = $this->actingAs($client)->post(route('reviews.store'), [
        // reviewer_id spoofado deliberadamente: deve ser ignorado.
        'reviewer_id' => $impersonated->id,
        'reviewable_type' => Service::class,
        'reviewable_id' => $service->id,
        'rating' => 5,
        'comment' => 'Muito bom.',
    ]);

    $response->assertRedirect(route('reviews.index'));

    $this->assertDatabaseHas('reviews', [
        'reviewer_id' => $client->id,
        'reviewable_id' => $service->id,
        'reviewable_type' => Service::class,
    ]);

    $this->assertDatabaseMissing('reviews', [
        'reviewer_id' => $impersonated->id,
    ]);
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
        'reviewable_type' => Service::class,
        'reviewable_id' => $service->id,
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
            'reviewable_type' => Service::class,
            'reviewable_id' => $service->id,
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
