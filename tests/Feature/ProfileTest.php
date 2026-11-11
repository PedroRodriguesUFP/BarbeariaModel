<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('profile page is displayed with the site layout', function () {
    $user = User::factory()->client()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Dados da conta')
        ->assertSee('Alterar palavra-passe');
});

test('profile information can be updated', function () {
    Notification::fake();
    $user = User::factory()->client()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHas('status', 'verification-link-sent');

    expect($user->refresh()->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com')
        ->and($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmail::class);
});
