<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

    // Utilizador já autenticado que abre /login vai para a sua área.
    $middleware->redirectUsersTo(
        fn (Request $request) => route($request->user()->homeRoute())
    );

    $middleware->alias([
        'role' => \App\Http\Middleware\RoleMiddleware::class,
    ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Regista os acessos recusados (403) de quem tem sessão iniciada.
        // Devolve null para o Laravel continuar a mostrar a página 403 normal.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($e->getStatusCode() === 403 && $request->user()) {
                Log::channel('auth')->notice('Acesso recusado', [
                    'user_id' => $request->user()->id,
                    'metodo'  => $request->method(),
                    'url'     => $request->path(),
                    'ip'      => $request->ip(),
                ]);
            }

            return null;
        });
    })->create();
