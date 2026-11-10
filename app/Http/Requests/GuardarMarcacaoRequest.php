<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação (e autorização) de criar e editar marcações.
 *
 * Criar (POST): a policy decide quem pode marcar.
 * Editar (PUT/PATCH): a policy olha para a marcação concreta.
 * Se a policy disser que não, o pedido termina em 403 antes de chegar ao controller.
 */
class GuardarMarcacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('POST')) {
            return $this->user()->can('create', Appointment::class);
        }

        return $this->user()->can('update', $this->route('appointment'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],

            // O utilizador escolhido tem mesmo de ser um barbeiro.
            'barber_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', UserRole::Barber->value),
            ],

            'service_id' => ['required', 'exists:services,id'],
        ];

        // Ao criar, o admin escolhe o cliente. O cliente marca sempre para si.
        if ($this->isMethod('POST') && $this->user()->isAdmin()) {
            $rules['customer_id'] = [
                'required',
                Rule::exists('users', 'id')->where('role', UserRole::Client->value),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'date.required'        => 'Indique a data.',
            'date.after_or_equal'  => 'A data não pode ser anterior a hoje.',
            'time.required'        => 'Indique a hora.',
            'time.date_format'     => 'A hora tem de estar no formato HH:MM.',
            'barber_id.required'   => 'Escolha um barbeiro.',
            'barber_id.exists'     => 'O barbeiro escolhido não é válido.',
            'service_id.required'  => 'Escolha um serviço.',
            'customer_id.required' => 'Escolha o cliente.',
            'customer_id.exists'   => 'O cliente escolhido não é válido.',
        ];
    }
}
