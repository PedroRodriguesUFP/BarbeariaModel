<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação (e autorização) de criar e editar pagamentos.
 *
 * O admin preenche tudo. Os outros só enviam marcação e forma de pagamento:
 * valor, estado e data de pagamento nunca vêm de um cliente.
 */
class GuardarPagamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('POST')) {
            return $this->user()->can('create', Payment::class);
        }

        return $this->user()->can('update', $this->route('payment'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'appointment_id' => ['required', 'exists:appointments,id'],
            'method'         => ['required', Rule::enum(PaymentMethod::class)],
        ];

        if ($this->user()->isAdmin()) {
            $rules += [
                'amount'  => ['required', 'numeric', 'min:0'],
                'status'  => ['required', Rule::enum(PaymentStatus::class)],
                'paid_at' => ['nullable', 'date'],
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'appointment_id.required' => 'Indique a marcação.',
            'appointment_id.exists'   => 'A marcação indicada não existe.',
            'method.required'         => 'Escolha a forma de pagamento.',
            'amount.required'         => 'Indique o valor.',
            'amount.min'              => 'O valor não pode ser negativo.',
            'status.required'         => 'Indique o estado.',
        ];
    }
}
