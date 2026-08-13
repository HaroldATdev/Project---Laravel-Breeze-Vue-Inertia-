<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ficha técnica del producto (catálogo puro).
 *
 * El stock físico NO se registra en el momento de la creación del producto: la
 * tabla sólo alberga la ficha (nombre, marca, tipo, presentación, precio) y el
 * `min_stock` (punto de reorden). El stock real (`current_stock`) se inicializa
 * en 0 y sólo puede modificarse a través de movimientos de kardex.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand');
            $table->string('type');                 // Tinto, Blanco, Rosado, Espumoso...
            $table->string('presentation');         // 750 ml, 1 L, 3 L...
            $table->decimal('sale_price', 10, 2);
            $table->unsignedInteger('min_stock')->default(0);   // Punto de reorden.
            $table->unsignedInteger('current_stock')->default(0); // Sólo movilizado vía kardex.
            $table->timestamps();

            $table->index(['brand', 'type']);
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
