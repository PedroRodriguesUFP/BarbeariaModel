<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

function verificationRegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Maria Costa',
        'email' => 'maria@example.com',
        'phone' => '912345678',
        'password' => 'palavra-passe-segura',
        'password_confirmation' => 'palavra-passe-segura',
    ], $overrides);
}

test('new registration sends a verification email and opens the notice', function () {
    Notification::fake();

    $this->post(route('register'), verificationRegistrationPayload())
        ->assertRedirect(route('verification.notice'));

    $user = User::query()->where('email', 'maria@example.com')->firstOrFail();

    expect($user->role)->toBe(UserRole::Client)
        ->and($user->email_verified_at)->toBeNull();

    $this->assertAuthenticatedAs($user);
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('unverified user can open the notice and resend the verification email', function () {
    Notification::fake();
    $user = User::factory()->client()->create(['email_verified_at' => null]);

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Verificar endereço de email');

    $this->post(route('verification.send'))
        ->assertRedirect()
        ->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('verification email resend is rate limited', function () {
    Notification::fake();
    $user = User::factory()->client()->create(['email_verified_at' => null]);
    $response = null;

    foreach (range(1, 7) as $attempt) {
        $response = $this->actingAs($user)->post(route('verification.send'));
    }

    $response->assertTooManyRequests();
});

test('unverified user is redirected from protected application and profile pages', function () {
    $user = User::factory()->client()->create(['email_verified_at' => null]);

    $this->actingAs($user)
        ->get(route('appointments.index'))
        ->assertRedirect(route('verification.notice'));

    $this->get(route('profile.edit'))
        ->assertRedirect(route('verification.notice'));
});

test('valid signed verification link verifies the user', function () {
    $user = User::factory()->client()->create(['email_verified_at' => null]);
    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(30),
        [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ],
    );

    $this->actingAs($user)
        ->get($verificationUrl)
        ->assertRedirect(route('appointments.index'))
        ->assertSessionHas('success', 'Email verificado com sucesso.');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    $this->get(route('appointments.index'))->assertOk();
});

test('invalid signed verification link does not verify the user', function () {
    $user = User::factory()->client()->create(['email_verified_at' => null]);
    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(30),
        [
            'id' => $user->id,
            'hash' => sha1('different@example.com'),
        ],
    );

    $this->actingAs($user)
        ->get($verificationUrl)
        ->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('admin-created accounts are verified immediately', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Barbeiro de teste',
            'email' => 'barbeiro@example.com',
            'phone' => '912345678',
            'password' => 'palavra-passe-segura',
            'password_confirmation' => 'palavra-passe-segura',
            'role' => UserRole::Barber->value,
        ])
        ->assertRedirect(route('admin.users.index'));

    expect(User::query()->where('email', 'barbeiro@example.com')->firstOrFail()->hasVerifiedEmail())
        ->toBeTrue();
});

test('existing accounts are grandfathered by the verification migration', function () {
    $legacyUser = User::factory()->client()->create([
        'email_verified_at' => null,
    ]);
    $migration = require database_path('migrations/2026_11_11_104430_grandfather_existing_verified_emails.php');

    $migration->up();

    expect($legacyUser->fresh()->hasVerifiedEmail())->toBeTrue();
});
