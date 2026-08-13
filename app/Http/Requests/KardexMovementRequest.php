<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para el registro de movimientos de kardex.
 *
 * Permite registrar movimientos de entrada y ajuste manual. Las salidas
 * (venta) sólo pueden efectuarse desde SaleController::store, preservando el
 * flujo transaccional y el bloqueo de fila.
 */
class KardexMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'movement_type' => ['required', 'in:entrada,ajuste'],
            'quantity' => ['required', 'integer', 'min:-999999'],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.exists' => 'El producto seleccionado no existe.',
            'movement_type.in' => 'El tipo de movimiento no es válido.',
            'quantity.integer' => 'La cantidad debe ser un número entero.',
        ];
    }
}
