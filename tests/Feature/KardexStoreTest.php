<?php

namespace Tests\Feature;

use App\Models\KardexMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QA — Gestión de stock vía kardex (regla #2).
 *
 * El stock físico sólo se modifica a través de movimientos de kardex
 * (entradas / ajustes) registrados por KardexController::store, que:
 *  - actualiza `current_stock` del producto dentro de la misma transacción,
 *  - registra el movimiento con stock anterior / nuevo (trazabilidad),
 *  - rechaza movimientos inconsistentes (negativo, ajuste cero, tipo venta).
 */
class KardexStoreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_registering_an_entrada_increases_stock_and_creates_movement(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->user)->post('/kardex', [
            'product_id' => $product->id,
            'movement_type' => 'entrada',
            'quantity' => 10,
            'reference' => 'FACTURA-001',
        ])->assertRedirect(route('kardex.index'));

        $product->refresh();
        $this->assertSame(10, (int) $product->current_stock);

        $movement = KardexMovement::where('product_id', $product->id)->first();
        $this->assertNotNull($movement);
        $this->assertSame('entrada', $movement->movement_type);
        $this->assertSame(10, (int) $movement->quantity);
        $this->assertSame(0, (int) $movement->previous_stock);
        $this->assertSame(10, (int) $movement->new_stock);
        $this->assertSame('FACTURA-001', $movement->reference);
        $this->assertSame(1, KardexMovement::count());
    }

    public function test_registering_several_movements_keeps_running_stock_correct(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->user)->post('/kardex', [
            'product_id' => $product->id, 'movement_type' => 'entrada', 'quantity' => 20, 'reference' => 'INI',
        ])->assertRedirect(route('kardex.index'));
        $this->actingAs($this->user)->post('/kardex', [
            'product_id' => $product->id, 'movement_type' => 'ajuste', 'quantity' => -5, 'reference' => 'AJUSTE-01',
        ])->assertRedirect(route('kardex.index'));
        $this->actingAs($this->user)->post('/kardex', [
            'product_id' => $product->id, 'movement_type' => 'entrada', 'quantity' => 3, 'reference' => 'FACT-02',
        ])->assertRedirect(route('kardex.index'));

        $product->refresh();
        $this->assertSame(18, (int) $product->current_stock);

        // El balance del kardex (entradas - salidas + ajustes) == current_stock.
        $balance = KardexMovement::where('product_id', $product->id)->sum('quantity');
        $this->assertSame((int) $product->current_stock, (int) $balance);
        $this->assertSame(3, KardexMovement::count());
    }

    public function test_ajuste_cannot_deplete_stock_below_zero(): void
    {
        $product = Product::factory()->create(['current_stock' => 2]);

        // Un ajuste de -5 no está permitido: dejaría el stock en negativo.
        $this->actingAs($this->user)->post('/kardex', [
            'product_id' => $product->id, 'movement_type' => 'ajuste', 'quantity' => -5, 'reference' => 'AJUSTE-BAD',
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(2, $product->fresh()->current_stock);
        $this->assertSame(0, KardexMovement::count());
    }

    public function test_entrada_must_be_positive(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->user)->post('/kardex', [
            'product_id' => $product->id, 'movement_type' => 'entrada', 'quantity' => -4, 'reference' => 'X',
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(0, $product->fresh()->current_stock);
        $this->assertSame(0, KardexMovement::count());
    }

    public function test_kardex_does_not_allow_recording_venta_movements(): void
    {
        $product = Product::factory()->create(['current_stock' => 5]);

        $this->actingAs($this->user)->post('/kardex', [
            'product_id' => $product->id, 'movement_type' => 'venta', 'quantity' => 1,
        ])->assertSessionHasErrors('movement_type');

        // El stock no se moviliza desde el kardex; las ventas pasan por /sales.
        $this->assertSame(5, $product->fresh()->current_stock);
        $this->assertSame(0, KardexMovement::count());
    }

    public function test_register_validates_required_fields(): void
    {
        $this->actingAs($this->user)->post('/kardex', [])
            ->assertSessionHasErrors(['product_id', 'movement_type', 'quantity']);

        $this->assertSame(0, KardexMovement::count());
    }
}
