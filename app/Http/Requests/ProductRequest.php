<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para la ficha técnica del producto.
     *
     * El producto es un catálogo puro: el stock físico NO se ingresa al crearlo.
     * `min_stock` es el único dato de inventario aceptado al registrar/edtar
     * el producto (punto de reorden) y `current_stock` se gestiona exclusivamente
     * a través de movimientos de kardex.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'presentation' => ['required', 'string', 'max:255'],
            'sale_price' => ['required', 'numeric', 'min:0', 'max:999999', 'decimal:0,2'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'sale_price.min' => 'El precio de venta no puede ser negativo.',
            'sale_price.max' => 'El precio de venta es demasiado alto.',
            'sale_price.decimal' => 'El precio de venta debe tener como máximo dos decimales.',
            'min_stock.integer' => 'El stock mínimo debe ser un número entero.',
            'min_stock.min' => 'El stock mínimo no puede ser negativo.',
        ];
    }
}
