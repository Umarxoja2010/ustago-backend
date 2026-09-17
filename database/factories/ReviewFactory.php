<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\MasterProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Review> */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory()->state(['status' => 'completed']),
            'user_id' => User::factory(),
            'master_profile_id' => MasterProfile::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->sentence(),
            'hidden' => false,
        ];
    }
}
