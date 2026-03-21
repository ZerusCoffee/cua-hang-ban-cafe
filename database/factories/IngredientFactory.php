<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ingredient>
 */
class IngredientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'       => $this->faker->unique()->words(2, true),
            'unit_id'    => Unit::inRandomOrder()->value('id') ?? Unit::factory(),
            'cost_price' => 0,
            'stock'      => 0,
            'threshold'  => $this->faker->numberBetween(50, 500),
        ];
    }

     public function withStock(int $stock = 1000, int $costPrice = 50000): static
    {
        return $this->state([
            'stock'      => $stock,
            'cost_price' => $costPrice,
        ]);
    }
}
