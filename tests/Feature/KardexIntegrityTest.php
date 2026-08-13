<?php

namespace Tests\Feature;

use App\Models\KardexMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QA — Integridad del kardex: el balance de entradas menos salidas debe
 * coincidir exactamente con el stock actual de cada producto.
 */
class KardexIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_kardex_balance_matches_current_stock_after_multiple_sales(): void
    {
        $user = User::factory()->create();
        $wine = Product::factory()->create(['current_stock' => 50, 'sale_price' => 100.00]);
        $sparkling = Product::factory()->create(['current_stock' => 30, 'sale_price' => 200.00]);

        // Entradas iniciales en el kardex.
        $seedEntries = [
            ['product' => $wine, 'qty' => 50],
            ['product' => $sparkling, 'qty' => 30],
        ];
        foreach ($seedEntries as $row) {
            KardexMovement::create([
                'product_id' => $row['product']->id,
                'movement_type' => KardexMovement::TYPE_ENTRADA,
                'quantity' => $row['qty'],
                'previous_stock' => 0,
                'new_stock' => $row['qty'],
                'reference' => 'STOCK INICIAL',
            ]);
        }

        // Ventas múltiples.
        $this->actingAs($user)->post('/sales', [
            'items' => [['product_id' => $wine->id, 'quantity' => 5]],
        ])->assertRedirect();

        $this->actingAs($user)->post('/sales', [
            'items' => [
                ['product_id' => $wine->id, 'quantity' => 3],
                ['product_id' => $sparkling->id, 'quantity' => 10],
            ],
        ])->assertRedirect();

        // Stock esperado: 50 - 5 - 3 = 42 y 30 - 10 = 20.
        $this->assertSame(42, $wine->fresh()->current_stock);
        $this->assertSame(20, $sparkling->fresh()->current_stock);

        // Para cada producto, el balance del kardex debe ser idéntico al stock actual.
        foreach (Product::all() as $product) {
            $entradas = (int) KardexMovement::where('product_id', $product->id)
                ->where('movement_type', KardexMovement::TYPE_ENTRADA)->sum('quantity');
            $salidas = (int) KardexMovement::where('product_id', $product->id)
                ->where('movement_type', KardexMovement::TYPE_VENTA)->sum('quantity');
            $ajustes = (int) KardexMovement::where('product_id', $product->id)
                ->where('movement_type', KardexMovement::TYPE_AJUSTE)->sum('quantity');

            // Suma álgebraica con el signo de cada movimiento.
            $this->assertSame(
                (int) $product->current_stock,
                $entradas + $salidas + $ajustes,
                "El balance del kardex no coincide con el stock del producto {$product->name}.",
            );

            // Forma explícita: entradas - salidas (+ ajustes con su signo).
            $this->assertSame(
                (int) $product->current_stock,
                $entradas - abs($salidas) + $ajustes,
                "Entradas - salidas no coincide con el stock del producto {$product->name}.",
            );
        }

        // El kardex contiene el total de movimientos esperados (2 entradas + 3 salidas).
        $this->assertSame(5, KardexMovement::count());
    }

    public function test_rejected_sale_does_not_break_kardex_balance(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['current_stock' => 5]);
        KardexMovement::create([
            'product_id' => $product->id,
            'movement_type' => KardexMovement::TYPE_ENTRADA,
            'quantity' => 5,
            'previous_stock' => 0,
            'new_stock' => 5,
            'reference' => 'STOCK INICIAL',
        ]);

        // Intento de venta que excede el stock -> rechazada con rollback.
        $this->actingAs($user)->post('/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 6]],
        ])->assertSessionHasErrors();

        $this->assertSame(5, $product->fresh()->current_stock);
        $this->assertSame(1, KardexMovement::count()); // solo la entrada inicial, sin venta fantasma.

        $this->assertSame(
            (int) $product->fresh()->current_stock,
            (int) KardexMovement::where('product_id', $product->id)->sum('quantity'),
        );
    }
}