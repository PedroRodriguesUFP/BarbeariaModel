<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class Payment extends Model
{
    protected $fillable = [
        'client_id',
        'barber_id',
        'appointment_id',
        'amount',
        'method',
        'status',
        'paid_at',
    ];

    // Define a conversão dos atributos do modelo.
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    // Devolve o cliente associado ao pagamento.
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // Devolve o barbeiro associado ao pagamento.
    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }

    // Devolve a marcação associada ao pagamento.
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    // Verifica se o pagamento está pendente.
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    // Verifica se o pagamento foi realizado.
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    // Verifica se o pagamento falhou.
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    // Verifica se o pagamento foi reembolsado.
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    // Regista o pagamento como realizado.
    public function markAsPaid(): void
    {
        $this->status = 'paid';
        $this->paid_at = now();

        $this->save();
    }

    // Regista o pagamento como falhado.
    public function markAsFailed(): void
    {
        $this->status = 'failed';

        $this->save();
    }

    // Verifica se o pagamento pode ser reembolsado.
    public function canBeRefunded(): bool
    {
        return $this->isPaid();
    }

    // Regista o pagamento como reembolsado.
    public function markAsRefunded(): void
    {
        if (! $this->canBeRefunded()) {
            throw new LogicException(
                'This payment cannot be refunded.'
            );
        }

        $this->status = 'refunded';

        $this->save();
    }

    // Formata o valor do pagamento em euros.
    public function getFormattedAmount(): string
    {
        return number_format(
            (float) $this->amount,
            2,
            ',',
            '.'
        ).' €';
    }
}
