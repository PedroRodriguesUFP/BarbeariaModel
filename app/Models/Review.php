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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }

    public function isNegative(): bool
    {
        return $this->rating <= 2;
    }

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

    public function updateComment(?string $comment): void
    {
        $this->comment = $comment;
        $this->save();
    }

    public function isAboutBarber(): bool
    {
        return $this->reviewable_type ===
            (new Barber)->getMorphClass();
    }

    public function isAboutService(): bool
    {
        return $this->reviewable_type ===
            (new Service)->getMorphClass();
    }
}
