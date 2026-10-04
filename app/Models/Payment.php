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

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }
    public function markAsPaid(): void
    {
        $this->status = 'paid';
        $this->paid_at = now();

        $this->save();
    }
    public function markAsFailed(): void
    {
        $this->status = 'failed';

        $this->save();
    }
    public function canBeRefunded(): bool
    {
        return $this->isPaid();
    }
    public function markAsRefunded(): void
    {
        if (!$this->canBeRefunded()) {
            throw new LogicException(
                'Este pagamento não pode ser reembolsado.'
            );
        }

        $this->status = 'refunded';

        $this->save();
    }
    
    public function getFormattedAmount(): string
    {
        return number_format(
            (float) $this->amount,
            2,
            ',',
            '.'
        ) . ' €';
    }
}