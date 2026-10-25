<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleLoginController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')
        ->stateless()
        ->user();

        // Procura primeiro pelo Google ID.
        $user = User::where(
            'google_id',
            $googleUser->getId()
        )->first();

        // Se já existir uma conta com o mesmo email,
        // associa essa conta ao Google.
        if (! $user) {
            $user = User::where(
                'email',
                $googleUser->getEmail()
            )->first();

            if ($user) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                ]);
            }
        }

        // Se não existir conta, cria um cliente novo.
        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'password' => null,
                'role' => UserRole::Client->value,
                'active' => true,
            ]);
        }

        // Não deixa contas desativadas entrar.
        if (! $user->active) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Esta conta encontra-se desativada.',
                ]);
        }

        Auth::login($user);

        request()->session()->regenerate();

        return redirect()->intended(
            route($user->homeRoute())
        );
    }
}