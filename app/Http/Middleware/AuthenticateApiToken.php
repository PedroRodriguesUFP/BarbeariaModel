<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica pedidos da API através do cabeçalho "Authorization: Bearer <token>".
 *
 * Responde 401 se o token faltar, não existir, tiver expirado, ou se a conta
 * estiver desativada. Em caso de sucesso, o utilizador fica disponível em
 * $request->user() e nas Policies, tal como na parte web.
 */
class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();

        if (! $plain) {
            return $this->unauthorized('Token em falta.');
        }

        $token = ApiToken::with('user')
            ->where('token', ApiToken::hash($plain))
            ->first();

        if (! $token || $token->isExpired() || ! $token->user?->isActive()) {
            return $this->unauthorized('Token inválido ou expirado.');
        }

        // Evita uma escrita na BD em cada pedido.
        if (! $token->last_used_at || $token->last_used_at->diffInMinutes(now()) >= 5) {
            $token->forceFill(['last_used_at' => now()])->save();
        }

        Auth::setUser($token->user);
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('api_token', $token);

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        return response()->json(['message' => $message], 401, ['WWW-Authenticate' => 'Bearer']);
    }
}
