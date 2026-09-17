<?php

namespace Database\Factories;

use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MasterProfile> */
class MasterProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->mechanic(),
            'workshop_name' => fake()->company().' Avto Servis',
            'owner_name' => fake()->name(),
            'address' => fake()->streetAddress(),
            'district' => fake()->citySuffix(),
            'city' => 'Tashkent',
            'about' => fake()->sentence(),
            'lat' => fake()->latitude(41.22, 41.38),
            'lng' => fake()->longitude(69.17, 69.35),
            'experience_years' => fake()->numberBetween(1, 20),
            'verification_status' => 'verified',
            'rating' => 0,
            'review_count' => 0,
            'jobs_count' => 0,
            'is_open' => true,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['verification_status' => 'pending']);
    }
}
