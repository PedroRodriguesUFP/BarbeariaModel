<?php

namespace App\Models;

use App\Enums\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_minutes',
        'category',
    ];

    // Define a conversão dos atributos do modelo.
    protected function casts(): array
    {
        return [
            'category' => Category::class,
        ];
    }

    // Devolve as avaliações do serviço.
    public function reviews(): MorphMany
    {
        return $this->morphMany(
            Review::class,
            'reviewable'
        );
    }

    // Atualiza o preço do serviço.
    public function updatePrice(float $newPrice): void
    {
        $this->price = $newPrice;

        $this->save();
    }

    // Atualiza o nome do serviço.
    public function updateName(string $newName): void
    {
        $this->name = $newName;

        $this->save();
    }

    // Atualiza a duração do serviço.
    public function updateDuration(int $newDuration): void
    {
        $this->duration_minutes = $newDuration;

        $this->save();
    }

    // Atualiza a descrição do serviço.
    public function updateDescription(string $newDescription): void
    {
        $this->description = $newDescription;

        $this->save();
    }
    // Devolve os barbeiros que prestam o serviço.
    public function barbers(): BelongsToMany
{
    return $this->belongsToMany(
        Barber::class,
        'barber_service',
        'service_id',
        'barber_id'
    );
}
}
