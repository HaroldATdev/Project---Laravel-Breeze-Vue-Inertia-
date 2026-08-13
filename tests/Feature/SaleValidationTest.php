<?php

namespace Tests\Feature;

use App\Models\KardexMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QA — Pruebas de datos maliciosos / extremos en la creación de ventas y productos.
 */
class SaleValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_rejects_negative_quantities(): void
    {
        $product = Product::factory()->create(['current_stock' => 10]);

        $this->actingAs($this->user)->post('/sales', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => -3],
            ],
        ])->assertSessionHasErrors('items.0.quantity');

        $this->assertSame(0, Sale::count());
        $this->assertSame(10, $product->fresh()->current_stock);
    }

    public function test_rejects_zero_quantity(): void
    {
        $product = Product::factory()->create(['current_stock' => 10]);

        $this->actingAs($this->user)->post('/sales', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 0],
            ],
        ])->assertSessionHasErrors('items.0.quantity');

        $this->assertSame(0, Sale::count());
        $this->assertSame(10, $product->fresh()->current_stock);
    }

    public function test_rejects_quantity_exceeding_available_stock(): void
    {
        $product = Product::factory()->create(['current_stock' => 5]);

        $this->actingAs($this->user)->post('/sales', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertSessionHasErrors();

        // Rollback total: sin venta y sin movimientos.
        $this->assertSame(0, Sale::count());
        $this->assertSame(0, SaleItem::count());
        $this->assertSame(0, KardexMovement::count());
        $this->assertSame(5, $product->fresh()->current_stock);
    }

    public function test_rejects_nonexistent_product_ids(): void
    {
        $this->actingAs($this->user)->post('/sales', [
            'items' => [
                ['product_id' => 999999, 'quantity' => 1],
            ],
        ])->assertSessionHasErrors('items.0.product_id');

        $this->assertSame(0, Sale::count());
    }

    public function test_rejects_empty_and_malformed_items(): void
    {
        // Sin ítems
        $this->actingAs($this->user)->post('/sales', ['items' => []])
            ->assertSessionHasErrors('items');

        // ítems no es un arreglo
        $this->actingAs($this->user)->post('/sales', ['items' => 'no-es-array'])
            ->assertSessionHasErrors('items');

        // cantidad no numérica
        $product = Product::factory()->create(['current_stock' => 5]);
        $this->actingAs($this->user)->post('/sales', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 'abc'],
            ],
        ])->assertSessionHasErrors('items.0.quantity');

        $this->assertSame(0, Sale::count());
    }

    public function test_ignores_tampered_prices_in_sale_request(): void
    {
        // El precio se calcula en el servidor desde el producto; enviar precios con
        // 3 decimales en el request no debe alterar la venta.
        $product = Product::factory()->create(['sale_price' => 100.00, 'current_stock' => 10]);

        $this->actingAs($this->user)->post('/sales', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 99.999, 'line_total' => 0.001],
            ],
        ])->assertRedirect();

        $item = SaleItem::first();
        $this->assertNotNull($item);
        $this->assertSame('100.00', $item->unit_price);
        $this->assertSame('200.00', $item->line_total);

        $sale = Sale::first();
        $this->assertSame('200.00', $sale->subtotal);
    }

    public function test_rejects_product_price_with_more_than_two_decimals(): void
    {
        $this->actingAs($this->user)->post('/products', [
            'name' => 'Vino Precisión',
            'brand' => 'Bodega X',
            'type' => 'Tinto',
            'presentation' => '750 ml',
            'sale_price' => '12.345',
            ])->assertSessionHasErrors('sale_price');

        $this->assertSame(0, Product::count());
    }

    public function test_accepts_valid_price_with_two_decimals(): void
    {
        $this->actingAs($this->user)->post('/products', [
            'name' => 'Vino Correcto',
            'brand' => 'Bodega X',
            'type' => 'Tinto',
            'presentation' => '750 ml',
            'sale_price' => '12.34',
            ])->assertRedirect();

        $this->assertSame(1, Product::count());
    }
}