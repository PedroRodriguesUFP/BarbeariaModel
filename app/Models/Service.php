<?php

namespace App\Models;

use App\Enums\Category;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Serviço que a barbearia oferece (corte, barba, etc.).
 *
 * duration_minutes é usado para saber quanto tempo a marcação ocupa
 * na agenda do barbeiro.
 */
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

    // Avaliações feitas a este serviço.
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    // Barbeiros que realizam este serviço.
    // A tabela service_user liga serviços a utilizadores; o filtro garante
    // que só aparecem utilizadores com o papel de barbeiro.
    public function barbers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'service_user',
            'service_id',
            'user_id'
        )->where('users.role', UserRole::Barber->value);
    }

    // ------------------------------------------------------------------
    // Pequenos atalhos para atualizar dados
    // ------------------------------------------------------------------

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