<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'bio',
        'password',
        'role',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isBarber(): bool
    {
        return $this->role === UserRole::Barber;
    }

    public function isClient(): bool
    {
        return $this->role === UserRole::Client;
    }

    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    // Marcações feitas por este utilizador
    public function appointmentsAsCustomer(): HasMany
    {
        return $this->hasMany(
            Appointment::class,
            'customer_id'
        );
    }

    // Marcações em que este utilizador é o barbeiro
    public function appointmentsAsBarber(): HasMany
    {
        return $this->hasMany(
            Appointment::class,
            'barber_id'
        );
    }

    // Serviços que o utilizador realiza quando é barbeiro
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            Service::class,
            'service_user',
            'user_id',
            'service_id'
        );
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    public function updateName(string $newName): void
    {
        $this->name = $newName;
        $this->save();
    }

    public function updateEmail(string $newEmail): void
    {
        $this->email = $newEmail;
        $this->save();
    }

    public function updatePhone(string $phoneNumber): void
    {
        $this->phone = $phoneNumber;
        $this->save();
    }
    // Avaliações escritas por este utilizador.
public function reviewsWritten(): HasMany
{
    return $this->hasMany(
        Review::class,
        'reviewer_id'
    );
}

// Avaliações recebidas quando este utilizador é barbeiro.
public function reviewsReceived(): MorphMany
{
    return $this->morphMany(
        Review::class,
        'reviewable'
    );
}
}