<?php

namespace Database\Seeders;

use App\Models\Barber;
use App\Models\Client;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $client = Client::first();
        $service = Service::first();
        $barber = Barber::first();

        if ($client && $service) {
            $service->reviews()->create([
                'client_id' => $client->id,
                'rating' => 5,
                'comment' => 'Excellent service.',
            ]);
        }

        if ($client && $barber) {
            $barber->reviews()->create([
                'client_id' => $client->id,
                'rating' => 4,
                'comment' => 'Very good barber.',
            ]);
        }
    }
}