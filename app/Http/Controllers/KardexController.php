<?php

namespace App\Http\Controllers;

use App\Models\KardexMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KardexController extends Controller
{
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
            'filters' => [
                'product_id' => $request->input('product_id'),
                'type' => $request->string('type')->value(),
                'reference' => $request->string('reference')->value(),
            ],
            'movementTypes' => KardexMovement::TYPES,
        ]);
    }
}