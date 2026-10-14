<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'phone' => '911111111',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin->value,
        ]);

        User::create([
            'name' => 'Client User',
            'email' => 'client@example.com',
            'phone' => '912345678',
            'password' => Hash::make('password'),
            'role' => UserRole::Client->value,
        ]);

        User::create([
            'name' => 'Barber User',
            'email' => 'barber@example.com',
            'phone' => '923456789',
            'bio' => 'Professional barber with experience in classic and modern cuts.',
            'password' => Hash::make('password'),
            'role' => UserRole::Barber->value,
        ]);
    }
}