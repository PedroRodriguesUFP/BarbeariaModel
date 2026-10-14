<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $reviewer = User::where(
            'role',
            UserRole::Client->value
        )->first();

        $barber = User::where(
            'role',
            UserRole::Barber->value
        )->first();

        $service = Service::first();

        if ($reviewer && $service) {
            $service->reviews()->create([
                'reviewer_id' => $reviewer->id,
                'rating' => 5,
                'comment' => 'Excellent service.',
            ]);
        }

        if ($reviewer && $barber) {
            $barber->reviewsReceived()->create([
                'reviewer_id' => $reviewer->id,
                'rating' => 4,
                'comment' => 'Very good barber.',
            ]);
        }
    }
}