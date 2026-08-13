<?php

namespace App\Http\Controllers;

use App\Http\Requests\KardexMovementRequest;
use App\Models\KardexMovement;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class KardexController extends Controller
{
    /**
     * Listado de movimientos con filtros.
     */
    public function __invoke(Request $request): Response
    {
        $movements = KardexMovement::query()
            ->with(['product' => fn ($query) => $query->select('id', 'name', 'brand', 'type', 'presentation')])
            ->when($request->filled('product_id'), fn ($query) => $query->where('product_id', $request->integer('product_id')))
            ->when($request->filled('type'), fn ($query) => $query->where('movement_type', $request->string('type')))
            ->when($request->filled('reference'), fn ($query) => $query->where('reference', 'like', '%'.$request->string('reference').'%'))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Kardex/Index', [
            'movements' => $movements,
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'brand']),
            'movementTypes' => KardexMovement::TYPES,
        ]);
    }

    /**
     * Registrar un movimiento de kardex (entrada / ajuste) que abastece el stock.
     *
     * Regla de negocio (regla #2): el stock físico se gestiona EXCLUSIVAMENTE
     * a través de movimientos de kardex. Este método, dentro de una transacción
     * con bloqueo pesimista, actualiza `current_stock` del producto y registra
     * el movimiento con su stock anterior y nuevo, manteniendo la trazabilidad
     * contable. Se rechaza cualquier movimiento que deje el stock en negativo.
     */
    public function store(KardexMovementRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $type = $data['movement_type'];
        $quantity = (int) $data['quantity'];

        if ($type === KardexMovement::TYPE_ENTRADA && $quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Una entrada debe aumentar el stock (cantidad positiva).']);
        }

        if ($type === KardexMovement::TYPE_AJUSTE && $quantity === 0) {
            throw ValidationException::withMessages(['quantity' => 'El ajuste no puede ser cero.']);
        }

        DB::transaction(function () use ($data, $type, $quantity) {
            $product = Product::where('id', $data['product_id'])->lockForUpdate()->firstOrFail();

            $previousStock = (int) $product->current_stock;
            $newStock = $previousStock + $quantity;

            if ($newStock < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'El ajuste deja el stock en negativo para "'.$product->name.'" (actual: '.$previousStock.', intento: '.$quantity.').',
                ]);
            }

            $product->current_stock = $newStock;
            $product->save();

            KardexMovement::create([
                'product_id' => $product->id,
                'movement_type' => $type,
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'reference' => $data['reference'],
            ]);
        });

        return redirect()
            ->route('kardex.index')
            ->with('success', 'Movimiento de kardex registrado correctamente.');
    }
}
