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

    public function test_creating_a_product_registers_its_initial_kardex_movement(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/products', [
            'name' => 'Tinto Reserva Test',
            'brand' => 'Bodega Test',
            'type' => 'Tinto',
            'presentation' => '750 ml',
            'sale_price' => 15000,
            'initial_stock' => 25,
        ])->assertRedirect();

        $product = Product::where('name', 'Tinto Reserva Test')->first();
        $this->assertNotNull($product);
        $this->assertEquals(25, $product->current_stock);

        $movement = KardexMovement::where('product_id', $product->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals('entrada', $movement->movement_type);
        $this->assertEquals(0, $movement->previous_stock);
        $this->assertEquals(25, $movement->new_stock);
        $this->assertEquals('STOCK INICIAL', $movement->reference);
    }
}