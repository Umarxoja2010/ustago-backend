<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\MasterProfile;
use App\Models\MasterService;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Booking> */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'master_profile_id' => MasterProfile::factory(),
            'master_service_id' => MasterService::factory(),
            'vehicle_id' => null,
            'date' => now()->addDays(fake()->numberBetween(1, 14))->toDateString(),
            'time' => fake()->randomElement(['09:00', '10:30', '14:00', '16:30']),
            'status' => 'pending',
            'notes' => null,
        ];
    }
}
