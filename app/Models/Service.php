<?php

namespace App\Models;

use App\Enums\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Service extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_minutes',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'category' => Category::class,
        ];
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(
            Review::class,
            'reviewable'
        );
    }

    public function updatePrice(float $newPrice): void
    {
        $this->price = $newPrice;

        $this->save();
    }

    public function updateName(string $newName): void
    {
        $this->name = $newName;

        $this->save();
    }

    public function updateDuration(int $newDuration): void
    {
        $this->duration_minutes = $newDuration;

        $this->save();
    }

    public function updateDescription(string $newDescription): void
    {
        $this->description = $newDescription;

        $this->save();
    }
}
