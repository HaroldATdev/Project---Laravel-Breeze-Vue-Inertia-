<?php

namespace Tests\Feature;

use App\Models\KardexMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Catálogo puro: al crear un producto no se ingresa stock. Nace con
     * current_stock = 0 y sin movimientos de kardex asociados.
     */
    public function test_creating_a_product_borns_with_zero_stock_and_no_kardex_movement(): void
    {
        $this->actingAs($this->user)->post('/products', [
            'name' => 'Tinto Reserva Test',
            'brand' => 'Bodega Test',
            'type' => 'Tinto',
            'presentation' => '750 ml',
            'sale_price' => '150.00',
            'min_stock' => 5,
        ])->assertRedirect();

        $product = Product::where('name', 'Tinto Reserva Test')->first();
        $this->assertNotNull($product);
        $this->assertSame(0, (int) $product->current_stock);
        $this->assertSame(5, (int) $product->min_stock);
        $this->assertSame(0, KardexMovement::count());
    }

    public function test_creating_a_product_accepts_min_stock_zero_by_default(): void
    {
        $this->actingAs($this->user)->post('/products', [
            'name' => 'Sin Min Stock',
            'brand' => 'Bodega Test',
            'type' => 'Tinto',
            'presentation' => '750 ml',
            'sale_price' => '100.00',
        ])->assertRedirect();

        $product = Product::where('name', 'Sin Min Stock')->first();
        $this->assertNotNull($product);
        $this->assertSame(0, (int) $product->min_stock);
        $this->assertSame(0, (int) $product->current_stock);
    }

    public function test_creating_a_product_rejects_negative_min_stock(): void
    {
        $this->actingAs($this->user)->post('/products', [
            'name' => 'Min Negativo',
            'brand' => 'Bodega Test',
            'type' => 'Tinto',
            'presentation' => '750 ml',
            'sale_price' => '100.00',
            'min_stock' => -5,
        ])->assertSessionHasErrors('min_stock');

        $this->assertSame(0, Product::count());
    }

    public function test_creating_a_product_ignores_stock_payload_fields(): void
    {
        // current_stock e initial_stock no provienen del formulario: el producto
        // siempre nace con current_stock = 0, sin importar lo enviado.
        $this->actingAs($this->user)->post('/products', [
            'name' => 'Payload Test',
            'brand' => 'Bodega Test',
            'type' => 'Tinto',
            'presentation' => '750 ml',
            'sale_price' => '100.00',
            'min_stock' => 0,
            'current_stock' => 999,
            'initial_stock' => 999,
        ])->assertRedirect();

        $product = Product::where('name', 'Payload Test')->first();
        $this->assertSame(0, (int) $product->current_stock);
    }

    /**
     * Regla #3: un producto sin movimientos de kardex puede eliminarse.
     */
    public function test_can_delete_a_product_that_has_no_kardex_movements(): void
    {
        $product = Product::factory()->create();

                $this->actingAs($this->user)
            ->delete('/products/'.$product->id)
            ->assertRedirect('/products');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);

        $this->actingAs($this->user)->get('/products')->assertOk();
        $this->assertSame(0, Product::count());
    }

    /**
     * Regla #3: un producto con historial de kardex NO puede eliminarse.
     */
    public function test_cannot_delete_a_product_that_has_kardex_movements(): void
    {
        $product = Product::factory()->create(['current_stock' => 5]);

        KardexMovement::create([
            'product_id' => $product->id,
            'movement_type' => KardexMovement::TYPE_ENTRADA,
            'quantity' => 5,
            'previous_stock' => 0,
            'new_stock' => 5,
            'reference' => 'STOCK INICIAL',
        ]);

        $this->actingAs($this->user)
            ->delete('/products/'.$product->id)
            ->assertRedirect() // redirect()->back()
            ->assertSessionHas('error');

                $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertSame(1, Product::count());
    }

    /**
     * El flow completo: el controlador envía el flash por sesión, el
     * HandleInertiaRequests::share lo expone como `flash.success` y el layout
     * autenticado lo renderiza en el HTML de la página destino.
     */
    public function test_success_flash_message_is_rendered_by_the_layout(): void
    {
        $html = $this->followingRedirects()->actingAs($this->user)->post('/products', [
            'name' => 'Vino Flash',
            'brand' => 'Bodega Test',
            'type' => 'Tinto',
            'presentation' => '750 ml',
            'sale_price' => '100.00',
            'min_stock' => 0,
        ])->getContent();

        $this->assertStringContainsString('Producto creado correctamente.', $html);
    }

    public function test_error_flash_message_is_rendered_when_delete_is_blocked(): void
    {
        $product = Product::factory()->create();

        KardexMovement::create([
            'product_id' => $product->id,
            'movement_type' => KardexMovement::TYPE_ENTRADA,
            'quantity' => 1,
            'previous_stock' => 0,
            'new_stock' => 1,
            'reference' => 'STOCK INICIAL',
        ]);

        // Establecemos el referrer para que redirect()->back() vuelva al índice.
        $this->actingAs($this->user)->get('/products')->assertOk();

        $html = $this->followingRedirects()->actingAs($this->user)
            ->delete('/products/'.$product->id)
            ->getContent();

        $this->assertStringContainsString('No se puede eliminar el producto', $html);
        $this->assertStringContainsString('movimientos registrados en el kardex', $html);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
