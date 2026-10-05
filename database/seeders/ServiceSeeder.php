<?php

namespace Database\Seeders;

use App\Enums\Category;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        Service::create([
            'name' => 'Haircut',
            'description' => 'Classic haircut.',
            'price' => 12.00,
            'duration_minutes' => 30,
            'category' => Category::Hair,
        ]);

        Service::create([
            'name' => 'Beard',
            'description' => 'Beard trim and treatment.',
            'price' => 8.00,
            'duration_minutes' => 20,
            'category' => Category::Beard,
        ]);

        Service::create([
            'name' => 'Hair and Beard',
            'description' => 'Combined hair and beard service.',
            'price' => 18.00,
            'duration_minutes' => 45,
            'category' => Category::HairAndBeard,
        ]);

        Service::create([
            'name' => 'Fade',
            'description' => 'Fade haircut.',
            'price' => 14.00,
            'duration_minutes' => 35,
            'category' => Category::Fade,
        ]);

        Service::create([
            'name' => 'Coloring',
            'description' => 'Hair coloring service.',
            'price' => 25.00,
            'duration_minutes' => 60,
            'category' => Category::Coloring,
        ]);
    }
}