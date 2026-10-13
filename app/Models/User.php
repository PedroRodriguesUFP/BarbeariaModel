<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Define a conversão dos atributos do utilizador.
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // Always store passwords securely as hashes.
            'role' => UserRole::class,
        ];
    }

    // Verifica se o utilizador é administrador.
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    // Verifica se o utilizador é barbeiro.
    public function isBarber(): bool
    {
        return $this->role === UserRole::Barber;
    }

    // Verifica se o utilizador é cliente.
    public function isClient(): bool
    {
        return $this->role === UserRole::Client;
    }

    // Devolve o perfil de cliente.
    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    // Devolve o perfil de barbeiro.
    public function barber(): HasOne
    {
        return $this->hasOne(Barber::class);
    }

    // Verifica se o utilizador tem o papel indicado.
    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    // Verifica se o utilizador está ativo.
    public function isActive(): bool
    {
        return $this->active;
    }

    // Devolve o nome para apresentar.
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    // Atualiza o nome do utilizador.
    public function updateName(string $newName): void
    {
        $this->name = $newName;
        $this->save();
    }

    // Atualiza o email do utilizador.
    public function updateEmail(string $newEmail): void
    {
        $this->email = $newEmail;
        $this->save();
    }

    // Atualiza o telefone do utilizador.
    public function updatePhone(string $phoneNumber): void
    {
        $this->phone = $phoneNumber;
        $this->save();
    }
    // TODO: Add role update support.

}
