<?php

namespace App\Models;

use App\Enums\Category;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Service extends Model
{
    use HasFactory;

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

    // Avaliações feitas ao serviço.
    public function reviews(): MorphMany
    {
        return $this->morphMany(
            Review::class,
            'reviewable'
        );
    }

    // Barbeiros que realizam este serviço.
    public function barbers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'service_user',
            'service_id',
            'user_id'
        )->where(
            'users.role',
            UserRole::Barber->value
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