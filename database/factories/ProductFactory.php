<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * El producto nace como catálogo puro: stock 0 y min_stock 0. El stock físico
     * se asigna posteriormente mediante movimientos de kardex.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true) . ' Wine',
            'brand' => $this->faker->randomElement(['Concha y Toro', 'Santa Rita', 'Viña Montes', 'Catena Zapata', 'Undurraga']),
            'type' => $this->faker->randomElement(['Tinto', 'Blanco', 'Rosado', 'Espumoso']),
            'presentation' => $this->faker->randomElement(['750 ml', '1 L', '1.5 L', '500 ml']),
            'sale_price' => $this->faker->randomFloat(2, 5000, 50000),
            'min_stock' => 0,
            'current_stock' => 0,
        ];
    }

    /**
     * Estado de fábrica con stock físico ya abastecido (para tests de ventas).
     * El stock se simula como si viniera de un movimiento de entrada del kardex.
     */
    public function withStock(int $quantity, int $minStock = 0): self
    {
        return $this->state([
            'min_stock' => $minStock,
            'current_stock' => $quantity,
        ]);
    }
}
