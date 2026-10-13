<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;

class Review extends Model
{
    protected $fillable = [
    'client_id',
    'reviewable_type',
    'reviewable_id',
    'rating',
    'comment',
    ];

    // Devolve o cliente que fez a avaliação.
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // Devolve o barbeiro ou serviço avaliado.
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    // Verifica se a avaliação é positiva.
    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }

    // Verifica se a avaliação é negativa.
    public function isNegative(): bool
    {
        return $this->rating <= 2;
    }

    // Atualiza a classificação da avaliação.
    public function updateRating(int $rating): void
    {
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException(
                'The rating must be between 1 and 5.'
            );
        }

        $this->rating = $rating;
        $this->save();
    }

    // Atualiza o comentário da avaliação.
    public function updateComment(?string $comment): void
    {
        $this->comment = $comment;
        $this->save();
    }

    // Verifica se a avaliação é sobre um barbeiro.
    public function isAboutBarber(): bool
    {
        return $this->reviewable_type ===
            (new Barber)->getMorphClass();
    }

    // Verifica se a avaliação é sobre um serviço.
    public function isAboutService(): bool
    {
        return $this->reviewable_type ===
            (new Service)->getMorphClass();
    }
}
