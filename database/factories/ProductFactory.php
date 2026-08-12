<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true).' Wine',
            'brand' => $this->faker->randomElement(['Concha y Toro', 'Santa Rita', 'Viña Montes', 'Catena Zapata', 'Undurraga']),
            'type' => $this->faker->randomElement(['Tinto', 'Blanco', 'Rosado', 'Espumoso']),
            'presentation' => $this->faker->randomElement(['750 ml', '1 L', '1.5 L', '500 ml']),
            'sale_price' => $this->faker->randomFloat(2, 5000, 50000),
            'initial_stock' => $this->faker->numberBetween(10, 200),
            'current_stock' => 0,
        ];
    }
}