<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Emissão e revogação de tokens da API.
 */
class AuthTokenController extends Controller
{
    // POST /api/v1/auth/token
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = User::where('email', $data['email'])->first();

        // Mesma mensagem para email inexistente, password errada ou conta
        // desativada: não revela que contas existem.
        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)
            || ! $user->isActive() || ! $user->hasVerifiedEmail()) {
            Log::channel('auth')->notice('Login API falhado', [
                'email' => $data['email'],
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'Credenciais inválidas.',
            ]);
        }

        $plain = ApiToken::issueFor($user, $data['device_name'], days: 30);

        Log::channel('auth')->info('Token API emitido', ['user_id' => $user->id, 'ip' => $request->ip()]);

        return response()->json([
            'token' => $plain,
            'token_type' => 'Bearer',
            'expires_in_days' => 30,
            'user' => new UserResource($user),
        ], 201);
    }

    // DELETE /api/v1/auth/token  (revoga o token usado no pedido)
    public function destroy(Request $request): JsonResponse
    {
        $request->attributes->get('api_token')->delete();

        return response()->json(null, 204);
    }

    // GET /api/v1/me
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
