<?php

namespace Database\Factories;

use App\Models\MasterProfile;
use App\Models\MasterService;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MasterService> */
class MasterServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'master_profile_id' => MasterProfile::factory(),
            'service_id' => Service::factory(),
            'duration' => fake()->randomElement(['30 min', '1 hour', '2 hours']),
            'price' => fake()->numberBetween(5, 50) * 10000,
            'active' => true,
        ];
    }
}
