<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KardexMovement extends Model
{
    use HasFactory;

    public const TYPE_ENTRADA = 'entrada';
    public const TYPE_VENTA = 'venta';
    public const TYPE_AJUSTE = 'ajuste';

    public const TYPES = [
        self::TYPE_ENTRADA,
        self::TYPE_VENTA,
        self::TYPE_AJUSTE,
    ];

    protected $fillable = [
        'product_id',
        'movement_type',
        'quantity',
        'previous_stock',
        'new_stock',
        'reference',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'previous_stock' => 'integer',
        'new_stock' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}