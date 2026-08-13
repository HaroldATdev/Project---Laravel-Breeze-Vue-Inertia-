<?php

namespace Database\Seeders;

use App\Models\KardexMovement;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Vinos realistas con marca, tipo y presentación.
     *
     * Regla de negocio: el producto es un catálogo puro (stock 0). El stock físico
     * se abastece exclusivamente a través de movimientos de entrada del kardex,
     * por lo que aquí se crea primero el producto y luego se registra su movimiento
     * inicial de entrada, manteniendo la trazabilidad contable intacta.
     */
    public function run(): void
    {
        $wines = [
            ['name' => 'Reserva Cabernet Sauvignon', 'brand' => 'Concha y Toro', 'type' => 'Tinto', 'presentation' => '750 ml', 'sale_price' => 180, 'stock' => 120],
            ['name' => 'Gran Reserva Carmenere', 'brand' => 'Santa Rita', 'type' => 'Tinto', 'presentation' => '750 ml', 'sale_price' => 240, 'stock' => 80],
            ['name' => 'Chardonnay Barrel Fermented', 'brand' => 'Viña Montes', 'type' => 'Blanco', 'presentation' => '750 ml', 'sale_price' => 210, 'stock' => 95],
            ['name' => 'Sauvignon Blanc Single Vineyard', 'brand' => 'Concha y Toro', 'type' => 'Blanco', 'presentation' => '750 ml', 'sale_price' => 150, 'stock' => 140],
            ['name' => 'Rosé Provence Style', 'brand' => 'Mouton Cadet', 'type' => 'Rosado', 'presentation' => '750 ml', 'sale_price' => 170, 'stock' => 60],
            ['name' => 'Brut Nature Espumante', 'brand' => 'Undurraga', 'type' => 'Espumoso', 'presentation' => '750 ml', 'sale_price' => 280, 'stock' => 45],
            ['name' => 'Malbec Reserva', 'brand' => 'Catena Zapata', 'type' => 'Tinto', 'presentation' => '750 ml', 'sale_price' => 250, 'stock' => 110],
            ['name' => 'Pinot Noir Gran Reserva', 'brand' => 'Viña Ventisquero', 'type' => 'Tinto', 'presentation' => '750 ml', 'sale_price' => 190, 'stock' => 70],
            ['name' => 'Riesling Late Harvest', 'brand' => 'Concha y Toro', 'type' => 'Blanco', 'presentation' => '500 ml', 'sale_price' => 160, 'stock' => 55],
            ['name' => 'Gran Reserva Tinto Magnum', 'brand' => 'Santa Rita', 'type' => 'Tinto', 'presentation' => '1.5 L', 'sale_price' => 350, 'stock' => 30],
        ];

        foreach ($wines as $wine) {
            $qty = (int) $wine['stock'];
            unset($wine['stock']);

            // El producto nace como catálogo puro: stock 0, sin initial_stock.
            $product = Product::create([
                'name' => $wine['name'],
                'brand' => $wine['brand'],
                'type' => $wine['type'],
                'presentation' => $wine['presentation'],
                'sale_price' => $wine['sale_price'],
                'min_stock' => 0,
                'current_stock' => 0,
            ]);

            // El stock físico se registra como movimiento de entrada del kardex.
            KardexMovement::create([
                'product_id' => $product->id,
                'movement_type' => KardexMovement::TYPE_ENTRADA,
                'quantity' => $qty,
                'previous_stock' => 0,
                'new_stock' => $qty,
                'reference' => 'STOCK INICIAL',
            ]);

            $product->update(['current_stock' => $qty]);
        }
    }
}
