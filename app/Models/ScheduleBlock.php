<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Período em que o barbeiro não aceita marcações (folga, férias, consulta...).
 *
 * Se all_day for verdadeiro, o dia inteiro está bloqueado.
 * Nesse caso start_time e end_time guardam 00:00:00 e 23:59:59.
 */
class ScheduleBlock extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'all_day',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'date'    => 'date',
            'all_day' => 'boolean',
        ];
    }

    // O barbeiro a quem pertence o bloqueio.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAllDay(): bool
    {
        return $this->all_day;
    }
}