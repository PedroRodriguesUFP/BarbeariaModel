<?php

namespace App\Http\Requests;

use App\Models\Review;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação (e autorização) das avaliações.
 *
 * Criar (POST): avalia-se uma marcação e dão-se duas notas, uma ao barbeiro
 * e outra ao serviço. Que a marcação é do cliente, está concluída e ainda
 * não foi avaliada verifica-se no controller (depende dos dados na base).
 *
 * Editar (PUT/PATCH): só mudam a nota e o comentário. O alvo nunca muda.
 */
class GuardarAvaliacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('POST')) {
            return $this->user()->can('create', Review::class);
        }

        return $this->user()->can('update', $this->route('review'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('POST')) {
            return [
                'appointment_id'  => ['required', 'integer'],
                'barber_rating'   => ['required', 'integer', 'min:1', 'max:5'],
                'barber_comment'  => ['nullable', 'string', 'max:1000'],
                'service_rating'  => ['required', 'integer', 'min:1', 'max:5'],
                'service_comment' => ['nullable', 'string', 'max:1000'],
            ];
        }

        return [
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_id.required' => 'Escolha a marcação que quer avaliar.',
            'barber_rating.required'  => 'Dê uma nota de 1 a 5 ao barbeiro.',
            'service_rating.required' => 'Dê uma nota de 1 a 5 ao serviço.',
            'rating.required'         => 'Dê uma nota de 1 a 5.',
        ];
    }
}
