<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleStoreRequest;
use App\Models\KardexMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    /**
     * Listado de ventas con su detalle.
     */
    public function index(): Response
    {
        $sales = Sale::query()
            ->withSum('items as total_items', 'quantity')
            ->orderByDesc('created_at')
            ->paginate(10);

        return Inertia::render('Sales/Index', [
            'sales' => $sales,
        ]);
    }

    /**
     * Formulario de nueva venta.
     */
    public function create(): Response
    {
        return Inertia::render('Sales/Create', [
            'products' => Product::query()
                ->where('current_stock', '>', 0)
                ->orderBy('name')
                ->get(['id', 'name', 'brand', 'type', 'presentation', 'sale_price', 'current_stock']),
        ]);
    }

    /**
     * Registrar una venta de forma totalmente transaccional.
     *
     * En una única transacción de base de datos:
     *  1. Crear la venta y sus ítems (con subtotales calculados).
     *  2. Descontar el stock de cada producto (con bloqueo de fila para evitar carreras).
     *  3. Registrar el movimiento en el kardex (stock anterior y nuevo).
     *     4. Si algo falla, se hace rollback de todo.
     */
    public function store(SaleStoreRequest $request): RedirectResponse
    {
        try {
            $sale = DB::transaction(function () use ($request) {
                $items = [];
                $lockedProducts = [];

                $subtotal = 0;

                foreach ($request->validated('items') as $item) {
                    // Bloqueo pesimista: evita dos ventas concurrentes sobre el mismo producto.
                    $product = $lockedProducts[$item['product_id']] ??= Product::query()
                        ->where('id', $item['product_id'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    $quantity = (int) $item['quantity'];

                    // Validación estricta: no se permite salir si no hay stock.
                    if ($product->current_stock < $quantity) {
                        throw ValidationException::withMessages([
                            'items' => "Stock insuficiente para \"{$product->name}\". Disponible: {$product->current_stock}, solicitado: {$quantity}.",
                        ]);
                    }

                    $product->current_stock -= $quantity;
                    $product->save();

                    $lineTotal = round($product->sale_price * $quantity, 2);

                    $items[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $quantity,
                        'unit_price' => $product->sale_price,
                        'line_total' => $lineTotal,
                    ];

                    $subtotal += $lineTotal;
                }

                // Secuencial para el número de venta.
                $nextNumber = 'V-'.now()->format('Y').str_pad((string) (Sale::count() + 1), 5, '0', STR_PAD_LEFT);

                $sale = Sale::create([
                    'sale_number' => $nextNumber,
                    'customer_name' => $request->input('customer_name'),
                    'subtotal' => round($subtotal, 2),
                    'tax' => round($subtotal * (($request->float('tax_rate') ?: 0) / 100), 2),
                    'total' => round($subtotal * (1 + (($request->float('tax_rate') ?: 0) / 100)), 2),
                ]);

                foreach ($items as $item) {
                    $product = $lockedProducts[$item['product_id']];

                    SaleItem::create([...$item, 'sale_id' => $sale->id]);

                    // 3. Registrar movimiento de kardex con stock anterior y nuevo.
                    KardexMovement::create([
                        'product_id' => $product->id,
                        'movement_type' => KardexMovement::TYPE_VENTA,
                        'quantity' => -$item['quantity'],
                        'previous_stock' => $product->current_stock + $item['quantity'],
                        'new_stock' => $product->current_stock,
                        'reference' => $sale->sale_number,
                    ]);
                }

                // 1. Venta + ítems creados, 2. stock descontado y 3. kardex registrado.
                // Si cualquier paso falla, la excepción propaga y el framework hace rollback completo.
                return $sale;
            }, 3);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', 'No se pudo registrar la venta: '.$e->getMessage());
        }

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', 'Venta registrada correctamente. Número: '.$sale->sale_number);
    }

    /**
     * Detalle de una venta.
     */
    public function show(Sale $sale): Response
    {
        $sale->load(['items.product' => fn ($query) => $query->select('id', 'name', 'brand', 'type', 'presentation', 'sale_price')]);

        return Inertia::render('Sales/Show', [
            'sale' => $sale,
        ]);
    }
}