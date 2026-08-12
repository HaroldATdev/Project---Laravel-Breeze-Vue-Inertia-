<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\KardexMovement;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Listado de productos (inventario).
     */
    public function index(Request $request): Response
    {
        $products = Product::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%'.$request->string('search')->value().'%')
                        ->orWhere('brand', 'like', '%'.$request->string('search')->value().'%')
                        ->orWhere('type', 'like', '%'.$request->string('search')->value().'%');
                });
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => [
                'search' => $request->string('search')->value(),
                'type' => $request->string('type')->value(),
            ],
        ]);
    }

    /**
     * Formulario para crear un producto.
     */
    public function create(): Response
    {
        return Inertia::render('Products/Create');
    }

    /**
     * Guardar un producto nuevo.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['current_stock'] = $data['initial_stock'];

        $product = DB::transaction(function () use ($data) {
            $product = Product::create($data);

            // Todo stock inicial queda respaldado por su movimiento de kardex.
            KardexMovement::create([
                'product_id' => $product->id,
                'movement_type' => KardexMovement::TYPE_ENTRADA,
                'quantity' => $data['initial_stock'],
                'previous_stock' => 0,
                'new_stock' => $data['initial_stock'],
                'reference' => 'STOCK INICIAL',
            ]);

            return $product;
        });

        return redirect()
            ->route('products.edit', $product)
            ->with('success', 'Producto creado correctamente.');
    }

    /**
     * Formulario de edición.
     */
    public function edit(Product $product): Response
    {
        return Inertia::render('Products/Edit', [
            'product' => $product,
        ]);
    }

    /**
     * Actualizar datos del producto. El stock solo se puede ajustar vía kardex.
     */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = collect($request->validated())->except('initial_stock')->all();

        $product->update($data);

        return redirect()
            ->route('products.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Eliminación lógica / validada.
     */
    public function destroy(Product $product): RedirectResponse
    {
        // Si el producto tiene movimientos o ventas asociadas, se rehúsa la eliminación.
        $soldCount = $product->saleItems()->count();

        if ($soldCount > 0 || $product->current_stock > 0) {
            return redirect()
                ->route('products.index')
                ->with('error', 'No se puede eliminar un producto con stock o con ventas asociadas. Utilice un ajuste de inventario.');
        }

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Producto eliminado correctamente.');
    }
}