<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Pagamento de uma marcação.
 *
 * Estados: ver PaymentStatus (pending, paid, failed, refunded).
 * Formas de pagamento: ver PaymentMethod (cash, card, mbway, transfer).
 * Na base de dados continuam guardados como texto.
 */
class Payment extends Model
{
    protected $fillable = [
        'payer_id',
        'appointment_id',
        'amount',
        'method',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'  => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    // Utilizador que fez o pagamento.
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    // Marcação a que o pagamento diz respeito.
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    // ------------------------------------------------------------------
    // Estado
    // ------------------------------------------------------------------

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending->value;
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid->value;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::Failed->value;
    }

    public function isRefunded(): bool
    {
        return $this->status === PaymentStatus::Refunded->value;
    }

    // Só se pode reembolsar o que foi pago.
    public function canBeRefunded(): bool
    {
        return $this->isPaid();
    }

    public function markAsPaid(): void
    {
        $this->status  = PaymentStatus::Paid->value;
        $this->paid_at = now();
        $this->save();
    }

    public function markAsFailed(): void
    {
        $this->status = PaymentStatus::Failed->value;
        $this->save();
    }

    // Lança uma exceção se o pagamento ainda não foi pago.
    public function markAsRefunded(): void
    {
        if (! $this->canBeRefunded()) {
            throw new LogicException('This payment cannot be refunded.');
        }

        $this->status = PaymentStatus::Refunded->value;
        $this->save();
    }

    // ------------------------------------------------------------------
    // Apresentação
    // ------------------------------------------------------------------

    // Formato português: 1.234,50 €
    public function getFormattedAmount(): string
    {
        return number_format((float) $this->amount, 2, ',', '.') . ' €';
    }
}
