<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação de criar e editar serviços. Só o admin gere serviços.
 */
class GuardarServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'price'            => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'category'         => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'             => 'Indique o nome do serviço.',
            'price.required'            => 'Indique o preço.',
            'price.min'                 => 'O preço não pode ser negativo.',
            'duration_minutes.required' => 'Indique a duração em minutos.',
            'duration_minutes.min'      => 'A duração tem de ser de pelo menos 1 minuto.',
            'category.required'         => 'Escolha a categoria.',
        ];
    }
}
