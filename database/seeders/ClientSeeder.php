<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('role', UserRole::Client->value)->firstOrFail();

        Client::create([
            'user_id' => $user->id,
            'phone' => '912345678',
        ]);
    }
}