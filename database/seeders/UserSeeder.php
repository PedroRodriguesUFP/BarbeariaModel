<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin->value,
        ]);

        User::create([
            'name' => 'Client User',
            'email' => 'client@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Client->value,
        ]);

        User::create([
            'name' => 'Barber User',
            'email' => 'barber@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Barber->value,
        ]);
    }
}
