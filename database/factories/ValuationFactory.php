<?php

namespace Database\Factories;

use App\Models\Valuation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Valuation>
 */
class ValuationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $brand = fake()->randomElement(array_keys(config('phones.brands')));
        $models = config("phones.brands.{$brand}");

        $priceLow = fake()->numberBetween(50, 900);

        return [
            'brand' => $brand,
            'model' => fake()->randomElement($models)['name'],
            'storage' => fake()->randomElement(array_keys(config('phones.storages'))),
            'battery' => fake()->numberBetween(60, 100),
            'condition' => fake()->randomElement(array_keys(config('phones.conditions'))),
            'price_low' => $priceLow,
            'price_high' => $priceLow + fake()->numberBetween(10, 60),
            'confidence' => fake()->numberBetween(78, 96),
            'insight' => fake()->sentence(),
            'photos_count' => fake()->numberBetween(0, 5),
        ];
    }
}
