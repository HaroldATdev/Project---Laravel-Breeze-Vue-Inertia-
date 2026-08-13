<?php

namespace Tests\Feature;

use App\Models\KardexMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_sale_deducts_stock_and_registers_kardex_in_one_transaction(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'current_stock' => 10,
            'sale_price' => 100.00,
        ]);

        $response = $this->actingAs($user)->post('/sales', [
            'customer_name' => 'Cliente Demo',
            'tax_rate' => 19,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ]);

        $response->assertRedirect();

        // 1. La venta se creó con los totales correctos.
        $sale = Sale::first();
        $this->assertNotNull($sale);
        $this->assertEquals(300.00, (float) $sale->subtotal);
        $this->assertEquals(57.00, (float) $sale->tax);
        $this->assertEquals(357.00, (float) $sale->total);

        // Detalle de la venta con el snapshot del producto.
        $this->assertEquals(1, $sale->items()->count());
        $item = SaleItem::first();
        $this->assertEquals(3, $item->quantity);
        $this->assertEquals(300.00, (float) $item->line_total);

        // 2. El stock fue descontado.
        $product->refresh();
        $this->assertEquals(7, $product->current_stock);

        // 3. El kardex refleja el stock anterior y nuevo.
        $movement = KardexMovement::where('product_id', $product->id)->where('movement_type', 'venta')->first();
        $this->assertNotNull($movement);
        $this->assertEquals(10, $movement->previous_stock);
        $this->assertEquals(7, $movement->new_stock);
        $this->assertEquals(-3, $movement->quantity);
        $this->assertEquals($sale->sale_number, $movement->reference);
    }

    public function test_a_sale_cannot_exceed_available_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'current_stock' => 5,
        ]);

        $response = $this->actingAs($user)->post('/sales', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 6],
            ],
        ]);

        $response->assertSessionHasErrors('items');

        // Rollback completo: sin venta, sin ítems, sin movimientos, stock intacto.
        $this->assertSame(0, Sale::count());
        $this->assertSame(0, SaleItem::count());
        $this->assertSame(0, KardexMovement::count());
        $this->assertSame(5, $product->fresh()->current_stock);
    }

    public function test_a_sale_can_include_multiple_products_and_totals_are_consistent(): void
    {
        $user = User::factory()->create();
        $wine = Product::factory()->create(['sale_price' => 200.00, 'current_stock' => 20]);
        $sparkling = Product::factory()->create(['sale_price' => 150.00, 'current_stock' => 10]);

        $this->actingAs($user)->post('/sales', [
            'customer_name' => 'Enólogo VIP',
            'items' => [
                ['product_id' => $wine->id, 'quantity' => 2],
                ['product_id' => $sparkling->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $sale = Sale::first();
        $this->assertEquals(550.00, (float) $sale->subtotal);
        $this->assertEquals(550.00, (float) $sale->total);

        // Ambos productos fueron descontados y tienen su kardex.
        $this->assertEquals(18, $wine->fresh()->current_stock);
        $this->assertEquals(9, $sparkling->fresh()->current_stock);
        $this->assertSame(2, KardexMovement::where('movement_type', 'venta')->count());
    }
}