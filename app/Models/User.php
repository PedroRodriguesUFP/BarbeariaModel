<?php

namespace App\Models;

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

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isBarber(): bool
    {
        return $this->role === 'barber';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    public function barber(): HasOne
    {
        return $this->hasOne(Barber::class);
    }

public function UpdateName(string $newName): void
    {
        $this->name = $newName;
        $this->save();
    }

    public function UpdateEmail(string $newEmail): void
    {
        $this->email = $newEmail;
        $this->save();
    }

    public function UpdatePhone (string $phoneNumber): void 
    {
        $this->phone = $phoneNumber;
        $this->save();
    }
// pendente : falta criar o role
   // public function updateRole (  $role): void//

}