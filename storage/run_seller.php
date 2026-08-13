<?php
// Proceso hijo de prueba de concurrencia (QA).
// Se invoca como: php storage/run_seller.php <userId> <productId>
// Arranca Laravel, se conecta a la BD de testing y ejecuta el SaleController real.
require __DIR__.'/../vendor/autoload.php';

use App\Http\Controllers\SaleController;
use App\Http\Requests\SaleStoreRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$userId = (int) ($argv[1] ?? 0);
$productId = (int) ($argv[2] ?? 0);

// Fuerza la conexión a la base de datos de testing (la del proceso actual).
config(['database.connections.mysql.database' => 'testing']);
DB::purge('mysql');
DB::reconnect('mysql');

$user = User::findOrFail($userId);
$product = Product::findOrFail($productId);

// Pequeña pausa para garantizar que ambas peticiones estén "en vuelo" a la vez.
usleep(300000);

$request = Request::create('/sales', 'POST', [
    'customer_name' => 'Cliente Concurrente',
    'items' => [
        ['product_id' => $product->id, 'quantity' => 1],
    ],
]);
$request->setUserResolver(fn () => $user);

$saleRequest = SaleStoreRequest::createFrom($request);
$saleRequest->setUserResolver(fn () => $user);
$saleRequest->setContainer($app);

// IMPORTANTE: al invocar el controlador fuera del kernel HTTP, el FormRequest
// nunca se valida y ->validated() devuelve null (Error). Forzamos la validación
// aquí para poblar $this->validator, igual que el pipeline HTTP lo haría.
$saleRequest->validateResolved();

try {
    app(SaleController::class)->store($saleRequest);
    echo "OK\n";
    exit(0);
} catch (\Illuminate\Validation\ValidationException) {
    echo "STOCK_INSUFICIENTE\n";
    exit(0);
} catch (\Throwable $e) {
    echo 'ERR:'.get_class($e).':'.$e->getMessage()."\n";
    exit(1);
}