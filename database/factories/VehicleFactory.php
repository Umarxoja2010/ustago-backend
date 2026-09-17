<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vehicle> */
class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'brand' => fake()->randomElement(['Chevrolet', 'Kia', 'Hyundai', 'Toyota']),
            'model' => fake()->word(),
            'year' => (string) fake()->numberBetween(2005, 2025),
            'engine' => fake()->randomElement(['1.5L', '1.6L', '2.0L']),
            'plate' => strtoupper(fake()->bothify('##A###??')),
            'color' => fake()->safeColorName(),
        ];
    }
}
