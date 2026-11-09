<?php

use App\Models\User;

test('profile page is displayed with the site layout', function () {
    $user = User::factory()->client()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Dados da conta')
        ->assertSee('Alterar palavra-passe');
});

test('profile information can be updated', function () {
    $user = User::factory()->client()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('success');

    expect($user->refresh()->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com');
});
