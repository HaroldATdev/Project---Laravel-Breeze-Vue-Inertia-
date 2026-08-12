<?php

namespace Database\Seeders;

use App\Models\KardexMovement;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Vinos realistas con marca, tipo y presentación.
     * Al crear cada producto se registra también su movimiento inicial de kardex (entrada).
     */
    public function run(): void
    {
        $wines = [
            ['name' => 'Reserva Cabernet Sauvignon', 'brand' => 'Concha y Toro', 'type' => 'Tinto', 'presentation' => '750 ml', 'sale_price' => 18990, 'initial_stock' => 120],
            ['name' => 'Gran Reserva Carmenere', 'brand' => 'Santa Rita', 'type' => 'Tinto', 'presentation' => '750 ml', 'sale_price' => 24990, 'initial_stock' => 80],
            ['name' => 'Chardonnay Barrel Fermented', 'brand' => 'Viña Montes', 'type' => 'Blanco', 'presentation' => '750 ml', 'sale_price' => 21990, 'initial_stock' => 95],
            ['name' => 'Sauvignon Blanc Single Vineyard', 'brand' => 'Concha y Toro', 'type' => 'Blanco', 'presentation' => '750 ml', 'sale_price' => 15990, 'initial_stock' => 140],
            ['name' => 'Rosé Provence Style', 'brand' => 'Mouton Cadet', 'type' => 'Rosado', 'presentation' => '750 ml', 'sale_price' => 17990, 'initial_stock' => 60],
            ['name' => 'Brut Nature Espumante', 'brand' => 'Undurraga', 'type' => 'Espumoso', 'presentation' => '750 ml', 'sale_price' => 28990, 'initial_stock' => 45],
            ['name' => 'Malbec Reserva', 'brand' => 'Catena Zapata', 'type' => 'Tinto', 'presentation' => '750 ml', 'sale_price' => 25990, 'initial_stock' => 110],
            ['name' => 'Pinot Noir Gran Reserva', 'brand' => 'Viña Ventisquero', 'type' => 'Tinto', 'presentation' => '750 ml', 'sale_price' => 19990, 'initial_stock' => 70],
            ['name' => 'Riesling Late Harvest', 'brand' => 'Concha y Toro', 'type' => 'Blanco', 'presentation' => '500 ml', 'sale_price' => 16990, 'initial_stock' => 55],
            ['name' => 'Gran Reserva Tinto Magnum', 'brand' => 'Santa Rita', 'type' => 'Tinto', 'presentation' => '1.5 L', 'sale_price' => 35990, 'initial_stock' => 30],
        ];

        foreach ($wines as $wine) {
            $product = Product::create([
                ...$wine,
                'current_stock' => $wine['initial_stock'],
            ]);

            // Movimiento inicial de kardex (entrada de stock inicial).
            KardexMovement::create([
                'product_id' => $product->id,
                'movement_type' => KardexMovement::TYPE_ENTRADA,
                'quantity' => $wine['initial_stock'],
                'previous_stock' => 0,
                'new_stock' => $wine['initial_stock'],
                'reference' => 'STOCK INICIAL',
            ]);
        }
    }
}