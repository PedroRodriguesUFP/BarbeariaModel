<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where(
            'role',
            UserRole::Client->value
        )->firstOrFail();

        \App\Models\Client::create([
            'user_id' => $user->id,
            'phone' => '912345678',
        ]);
    }
}