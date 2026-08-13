<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Listado de productos (catálogo + inventario).
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
     * Formulario para crear un producto (catálogo puro, stock 0).
     */
    public function create(): Response
    {
        return Inertia::render('Products/Create');
    }

    /**
     * Guardar un producto nuevo.
     *
     * Regla de negocio: el producto es un catálogo puro. No se ingresa stock
     * inicial en este punto — nace con `current_stock = 0` y el stock físico se
     * abastecerá exclusivamente mediante movimientos de kardex (entradas,
     * ajustes, ...). Mantener la trazabilidad contable intacta.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['current_stock'] = 0; // Catálogo puro: nace sin stock físico.

        $product = DB::transaction(function () use ($data) {
            return Product::create($data);
        });

        return redirect()
            ->route('products.index', $product)
            ->with('success', 'Producto creado correctamente.');
    }

    /**
     * Formulario de edición. Sólo se editan campos de la ficha técnica, el
     * stock (`current_stock`) y el punto de reorden (`min_stock`). El stock
     * físico sólo se modifica vía kardex.
     */
    public function edit(Product $product): Response
    {
        return Inertia::render('Products/Edit', [
            'product' => $product,
        ]);
    }

    /**
     * Actualizar datos de la ficha técnica del producto.
     *
     * `current_stock` nunca proviene del formulario: sólo los movimientos de
     * kardex lo modifican. `min_stock` sí es editable (punto de reorden).
     */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = collect($request->validated())->only([
            'name',
            'brand',
            'type',
            'presentation',
            'sale_price',
            'min_stock',
        ])->all();

        $product->update($data);

        return redirect()
            ->route('products.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Eliminación física de un producto.
     *
     * Regla de negocio estricta: un producto SÓLO puede eliminarse si NO tiene
     * ningún movimiento registrado en el kardex. Si existe historial contable
     * (entradas, salidas o ajustes), se bloquea la eliminación preservando la
     * trazabilidad y se informa al usuario.
     */
    public function destroy(Product $product): RedirectResponse
    {
        if ($product->kardexMovements()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'No se puede eliminar el producto "'.$product->name.'" porque tiene movimientos registrados en el kardex. Gestione el stock/estado antes de eliminarlo.');
        }

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Producto eliminado correctamente.');
    }
}
