<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Enums\UserRole;

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

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
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

    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    public function barber(): HasOne
    {
        return $this->hasOne(Barber::class);
    }
    public function hasRole(UserRole $role):bool
    {
       return $this->role === $role;
    }
    public function isActive():bool
    {
        return $this->active;
    }
    public function getDisplayNameAttribute():string
    {
        return $this->name;
    }
}