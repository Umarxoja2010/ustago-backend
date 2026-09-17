<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+998'.fake()->unique()->numerify('#########'),
            'email_verified_at' => now(),
            'password' => 'password', // hashed automatically via the `hashed` cast
            'role' => 'customer',
            'city' => 'Tashkent',
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    public function mechanic(): static
    {
        return $this->state(fn () => ['role' => 'mechanic']);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }
}
