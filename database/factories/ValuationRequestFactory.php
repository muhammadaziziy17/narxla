<?php

namespace Database\Factories;

use App\Models\ValuationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ValuationRequest>
 */
class ValuationRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand' => fake()->randomElement(array_keys(config('phones.brands'))),
            'description' => fake()->sentence(12),
            'battery' => fake()->numberBetween(60, 100),
            'condition' => fake()->randomElement(array_keys(config('phones.conditions'))),
            'photos_count' => fake()->numberBetween(0, 5),
            'status' => 'new',
        ];
    }
}
