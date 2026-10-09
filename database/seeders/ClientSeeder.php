<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Client;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
Client::create([
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'phone' => '912345678',
            'id' => 1,
            
        ]);
    }
}
