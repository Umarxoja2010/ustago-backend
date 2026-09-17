<?php

namespace Database\Factories;

use App\Models\SpecialOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SpecialOffer> */
class SpecialOfferFactory extends Factory
{
    protected $model = SpecialOffer::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'subtitle' => fake()->sentence(6),
            'cta' => 'Batafsil',
            'image' => 'https://images.unsplash.com/photo-1486006920555-c77dcf18193c?auto=format&fit=crop&w=1200&q=80',
            'link' => '/app/search',
            'badge' => 'AKSIYA',
            'accent' => 'from-primary to-info',
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
