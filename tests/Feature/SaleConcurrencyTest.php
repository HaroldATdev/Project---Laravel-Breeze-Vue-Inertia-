<?php

namespace Tests\Feature;

use App\Models\KardexMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/**
 * QA — Tests de concurrencia / estrés.
 *
 * Para probar la concurrencia REAL se lanzan varios procesos PHP independientes
 * (uno por petición) que ejecutan el SaleController real a través del script de
 * ayuda `storage/run_seller.php`. Cada proceso abre su propia conexión MySQL
 * (no comparten conexión), por lo que el bloqueo de fila (SELECT ... FOR UPDATE)
 * del controlador se pone a prueba con conexiones verdaderamente simultáneas.
 */
class SaleConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_two_simultaneous_requests_cannot_both_buy_the_last_unit(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Vino Edición Única',
            'brand' => 'Bodega Concurrencia',
            'type' => 'Tinto',
            'presentation' => '750 ml',
            'sale_price' => 100.00,
            'current_stock' => 1,
        ]);

        KardexMovement::create([
            'product_id' => $product->id,
            'movement_type' => KardexMovement::TYPE_ENTRADA,
            'quantity' => 1,
            'previous_stock' => 0,
            'new_stock' => 1,
            'reference' => 'STOCK INICIAL',
        ]);

        $log1 = base_path('storage/concurrency-1.log');
        $log2 = base_path('storage/concurrency-2.log');
        @unlink($log1);
        @unlink($log2);

        // Dos peticiones simultáneas: procesos independientes, conexiones independientes.
        exec('php storage/run_seller.php '.$user->id.' '.$product->id.' > '.$log1.' 2>&1 &');
        exec('php storage/run_seller.php '.$user->id.' '.$product->id.' > '.$log2.' 2>&1 &');

        usleep(4_000_000); // espera a que ambos procesos terminen

        $r1 = is_file($log1) ? trim((string) file_get_contents($log1)) : 'PENDIENTE';
        $r2 = is_file($log2) ? trim((string) file_get_contents($log2)) : 'PENDIENTE';

        @unlink($log1);
        @unlink($log2);

        // Sólo una petición debe tener éxito; la otra debe fallar por stock.
        $this->assertSame(1, count(array_filter([$r1, $r2], fn ($r) => $r === 'OK')),
            "Debe haber exactamente 1 OK. Resultados: r1=[$r1] r2=[$r2]");
        $this->assertSame(1, count(array_filter([$r1, $r2], fn ($r) => $r === 'STOCK_INSUFICIENTE')),
            "Debe haber 1 rechazo por stock. Resultados: r1=[$r1] r2=[$r2]");

        // Invariantes tras la carrera.
        $this->assertSame(1, Sale::count());
        $this->assertSame(1, SaleItem::count());
        $this->assertSame(0, (int) $product->fresh()->current_stock);
        $this->assertSame(1, KardexMovement::where('movement_type', KardexMovement::TYPE_VENTA)->count());
        $this->assertSame(0, (int) KardexMovement::where('product_id', $product->id)->sum('quantity'));
    }

    public function test_three_simultaneous_requests_only_one_succeeds_when_stock_is_one(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Vino Edición Única (3 proc)',
            'brand' => 'Bodega Concurrencia',
            'type' => 'Tinto',
            'presentation' => '750 ml',
            'sale_price' => 100.00,
            'current_stock' => 1,
        ]);

        KardexMovement::create([
            'product_id' => $product->id,
            'movement_type' => KardexMovement::TYPE_ENTRADA,
            'quantity' => 1,
            'previous_stock' => 0,
            'new_stock' => 1,
            'reference' => 'STOCK INICIAL',
        ]);

        // Tres peticiones simultáneas: procesos independientes, conexiones independientes.
        $logs = [];
        for ($i = 1; $i <= 3; $i++) {
            $logs[$i] = base_path('storage/concurrency-'.$i.'.log');
            @unlink($logs[$i]);
        }

        for ($i = 1; $i <= 3; $i++) {
            exec('php storage/run_seller.php '.$user->id.' '.$product->id.' > '.$logs[$i].' 2>&1 &');
        }

        usleep(5_000_000); // espera a que terminen los 3 procesos

        $results = array_map(
            fn ($f) => is_file($f) ? trim((string) file_get_contents($f)) : 'PENDIENTE',
            $logs
        );

        foreach ($logs as $f) {
            @unlink($f);
        }

        // Sólo una de las tres peticiones debe concluir con éxito.
        $oks = count(array_filter($results, fn ($r) => $r === 'OK'));
        $stock = count(array_filter($results, fn ($r) => $r === 'STOCK_INSUFICIENTE'));

        $this->assertSame(1, $oks, "Debe haber exactamente 1 éxito. Resultados: ".json_encode($results));
        $this->assertSame(2, $stock, "Deben haber 2 rechazos por stock. Resultados: ".json_encode($results));

        $this->assertSame(1, Sale::count());
        $this->assertSame(1, SaleItem::count());
        $this->assertSame(0, (int) $product->fresh()->current_stock);
        $this->assertSame(1, KardexMovement::where('movement_type', KardexMovement::TYPE_VENTA)->count());
        $this->assertSame(0, (int) KardexMovement::where('product_id', $product->id)->sum('quantity'));
    }
}
