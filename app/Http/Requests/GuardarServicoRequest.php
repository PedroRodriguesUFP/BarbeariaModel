<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação de criar e editar serviços. Quem pode gerir serviços: ServicePolicy.
 */
class GuardarServicoRequest extends FormRequest
{
    // Criar (sem serviço na rota) ou editar (com serviço): a ServicePolicy decide.
    public function authorize(): bool
    {
        $service = $this->route('service');

        return $service
            ? $this->user()->can('update', $service)
            : $this->user()->can('create', Service::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'category' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Indique o nome do serviço.',
            'price.required' => 'Indique o preço.',
            'price.min' => 'O preço não pode ser negativo.',
            'duration_minutes.required' => 'Indique a duração em minutos.',
            'duration_minutes.min' => 'A duração tem de ser de pelo menos 1 minuto.',
            'category.required' => 'Escolha a categoria.',
        ];
    }
}
