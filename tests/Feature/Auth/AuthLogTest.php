<?php

use App\Enums\Status;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Log;

// Estes testes não escrevem no ficheiro de log. O canal "auth" é trocado por
// um falso que apenas guarda o que lhe pedem para registar.

function captureAuthLog(): ArrayObject
{
    $logged  = new ArrayObject();
    $channel = Mockery::mock();

    foreach (['info', 'warning', 'notice'] as $level) {
        $channel->shouldReceive($level)->andReturnUsing(
            function (string $message, array $context = []) use ($logged, $level) {
                $logged->append(['level' => $level, 'message' => $message, 'context' => $context]);
            }
        );
    }

    Log::partialMock()->shouldReceive('channel')->with('auth')->andReturn($channel);

    return $logged;
}

function loggedEntry(ArrayObject $logged, string $message): ?array
{
    foreach ($logged as $entry) {
        if ($entry['message'] === $message) {
            return $entry;
        }
    }

    return null;
}

test('successful login is logged with user id and ip', function () {
    $logged = captureAuthLog();
    $user   = User::factory()->client()->create(['password' => 'password']);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $entry = loggedEntry($logged, 'Sessão iniciada');

    expect($entry)->not->toBeNull()
        ->and($entry['level'])->toBe('info')
        ->and($entry['context']['user_id'])->toEqual($user->id)
        ->and($entry['context'])->toHaveKey('ip');
});

test('failed login is logged with the email and never with the password', function () {
    $logged = captureAuthLog();
    $user   = User::factory()->client()->create(['password' => 'password']);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'palavra-errada']);

    $entry = loggedEntry($logged, 'Login falhado');

    expect($entry)->not->toBeNull()
        ->and($entry['level'])->toBe('warning')
        ->and($entry['context']['email'])->toBe($user->email)
        ->and($entry['context'])->not->toHaveKey('password')
        ->and(json_encode($entry))->not->toContain('palavra-errada');
});

test('too many failed logins are logged as a lockout', function () {
    $logged = captureAuthLog();
    $user   = User::factory()->client()->create(['password' => 'password']);

    foreach (range(1, 6) as $attempt) {
        $this->post(route('login'), ['email' => $user->email, 'password' => 'errada']);
    }

    expect(loggedEntry($logged, 'Login bloqueado por excesso de tentativas'))->not->toBeNull();
});

test('logout is logged', function () {
    $logged = captureAuthLog();
    $user   = User::factory()->client()->create();

    $this->actingAs($user)->post(route('logout'));

    $entry = loggedEntry($logged, 'Sessão terminada');

    expect($entry)->not->toBeNull()
        ->and($entry['context']['user_id'])->toEqual($user->id);
});

test('registration is logged', function () {
    $logged = captureAuthLog();

    $this->post(route('register'), [
        'name'                  => 'Maria Costa',
        'email'                 => 'maria@example.com',
        'password'              => 'palavra-passe-segura',
        'password_confirmation' => 'palavra-passe-segura',
    ]);

    expect(loggedEntry($logged, 'Conta criada'))->not->toBeNull();
});

test('a denied access (403) is logged with user, method and url', function () {
    $logged  = captureAuthLog();
    $client  = User::factory()->client()->create();
    $other   = User::factory()->client()->create();

    $appointment = Appointment::create([
        'date'        => now()->addDay()->toDateString(),
        'time'        => '10:00',
        'customer_id' => $other->id,
        'service_id'  => Service::factory()->create()->id,
        'barber_id'   => User::factory()->barber()->create()->id,
    ]);

    $this->actingAs($client)
        ->get(route('appointments.show', $appointment))
        ->assertForbidden();

    $entry = loggedEntry($logged, 'Acesso recusado');

    expect($entry)->not->toBeNull()
        ->and($entry['level'])->toBe('notice')
        ->and($entry['context']['user_id'])->toEqual($client->id)
        ->and($entry['context']['metodo'])->toBe('GET')
        ->and($entry['context']['url'])->toContain('appointments');
});

test('hiding a button with @can does not fill the log with denied accesses', function () {
    $logged = captureAuthLog();
    $client = User::factory()->client()->create();

    // Marcação concluída: o cliente não a pode editar, por isso o botão fica escondido.
    Appointment::create([
        'date'        => now()->addDay()->toDateString(),
        'time'        => '10:00',
        'customer_id' => $client->id,
        'service_id'  => Service::factory()->create()->id,
        'barber_id'   => User::factory()->barber()->create()->id,
        'status'      => Status::Completed,
    ]);

    $this->actingAs($client)->get(route('appointments.index'))->assertOk();

    expect(loggedEntry($logged, 'Acesso recusado'))->toBeNull();
});
