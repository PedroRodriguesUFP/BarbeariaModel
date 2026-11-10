<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validação do registo público.
 *
 * Repara no que NÃO está aqui: o papel (role). Quem se regista é sempre
 * cliente, e isso é decidido no servidor, nunca por um campo do formulário.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Só visitantes chegam aqui: a rota está protegida pelo middleware "guest".
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'phone'    => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Indique o seu nome.',
            'email.required'     => 'Indique o seu email.',
            'email.email'        => 'Indique um email válido.',
            'email.unique'       => 'Já existe uma conta com este email.',
            'password.required'  => 'Escolha uma palavra-passe.',
            'password.confirmed' => 'A confirmação da palavra-passe não coincide.',
        ];
    }
}
