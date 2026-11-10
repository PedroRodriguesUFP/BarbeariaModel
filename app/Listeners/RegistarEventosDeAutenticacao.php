<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Escreve no canal de log "auth" (storage/logs/auth.log) o que acontece
 * à autenticação: quem entrou, quem falhou, quem ficou bloqueado.
 *
 * Regras:
 *  - nunca se regista a palavra-passe;
 *  - cada linha leva o IP, para se perceber um incidente mais tarde
 *    (e para o fail2ban, se o servidor o tiver, poder ler o ficheiro).
 *
 * O Laravel dispara estes eventos sozinho, tanto no login com email e
 * password como no login com Google. Por isso o registo está num só sítio.
 */
class RegistarEventosDeAutenticacao
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class         => 'aoIniciarSessao',
            Failed::class        => 'aoFalharLogin',
            Lockout::class       => 'aoBloquearLogin',
            Logout::class        => 'aoTerminarSessao',
            Registered::class    => 'aoCriarConta',
            PasswordReset::class => 'aoReporPassword',
        ];
    }

    public function aoIniciarSessao(Login $event): void
    {
        $this->log()->info('Sessão iniciada', [
            'user_id' => $event->user->getAuthIdentifier(),
            'ip'      => request()->ip(),
        ]);
    }

    public function aoFalharLogin(Failed $event): void
    {
        // Só o email. A password que a pessoa escreveu nunca vai para o log.
        $this->log()->warning('Login falhado', [
            'email' => $event->credentials['email'] ?? null,
            'ip'    => request()->ip(),
        ]);
    }

    public function aoBloquearLogin(Lockout $event): void
    {
        $this->log()->warning('Login bloqueado por excesso de tentativas', [
            'email' => $event->request->input('email'),
            'ip'    => $event->request->ip(),
        ]);
    }

    public function aoTerminarSessao(Logout $event): void
    {
        $this->log()->info('Sessão terminada', [
            'user_id' => $event->user?->getAuthIdentifier(),
            'ip'      => request()->ip(),
        ]);
    }

    public function aoCriarConta(Registered $event): void
    {
        $this->log()->info('Conta criada', [
            'user_id' => $event->user->getAuthIdentifier(),
            'ip'      => request()->ip(),
        ]);
    }

    public function aoReporPassword(PasswordReset $event): void
    {
        $this->log()->info('Password reposta', [
            'user_id' => $event->user->getAuthIdentifier(),
            'ip'      => request()->ip(),
        ]);
    }

    private function log()
    {
        return Log::channel('auth');
    }
}
