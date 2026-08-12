<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->unsignedInteger('initial_stock')->default(0);
            $table->unsignedInteger('current_stock')->default(0);
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
