<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Service> */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Engine repair', 'Oil change', 'Brake repair', 'Diagnostics',
            'Tire fitting', 'AC service', 'Battery replacement', 'Suspension repair',
        ]).' '.fake()->unique()->numberBetween(1, 100000);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'icon' => null,
            'color' => null,
        ];
    }
}
