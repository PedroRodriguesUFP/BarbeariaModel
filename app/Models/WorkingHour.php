<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Horário de trabalho do barbeiro num dia da semana.
 *
 * Um registo por barbeiro e por dia. Se não existir registo para um dia,
 * o barbeiro não trabalha nesse dia.
 *
 * day_of_week: 1 = segunda ... 6 = sábado, 0 = domingo
 * (é assim que o BarberController guarda).
 */
class WorkingHour extends Model
{
    protected $fillable = [
        'user_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    // O barbeiro a quem pertence este horário.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}