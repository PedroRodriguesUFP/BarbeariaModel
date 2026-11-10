<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Registo público de clientes.
 *
 * Barbeiros e admins não se registam aqui: são criados pelo admin.
 */
class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // O papel e o estado são decididos aqui, não pelo formulário.
        // A password é encriptada sozinha pelo cast 'hashed' do modelo User.
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'password' => $data['password'],
            'role'     => UserRole::Client,
            'active'   => true,
        ]);

        event(new Registered($user));

        // Entra logo depois de criar a conta.
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($user->homeRoute());
    }
}
