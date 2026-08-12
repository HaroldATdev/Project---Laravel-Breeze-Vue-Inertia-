<?php

use App\Http\Controllers\KardexController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SaleController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'stats' => [
            'products' => App\Models\Product::count(),
            'totalStock' => App\Models\Product::sum('current_stock'),
            'stockValue' => App\Models\Product::query()->selectRaw('SUM(current_stock * sale_price) as value')->value('value') ?? 0,
            'sales' => App\Models\Sale::count(),
            'lowStock' => App\Models\Product::where('current_stock', '<=', 10)->count(),
        ],
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Productos (inventario)
    Route::resource('products', ProductController::class)->except(['show']);

    // Kardex
    Route::get('kardex', KardexController::class)->name('kardex.index');

    // Ventas
    Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('sales/create', [SaleController::class, 'create'])->name('sales.create');
    Route::post('sales', [SaleController::class, 'store'])->name('sales.store');
    Route::get('sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
});

require __DIR__.'/auth.php';
