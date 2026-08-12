<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brand',
        'type',
        'presentation',
        'sale_price',
        'initial_stock',
        'current_stock',
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'initial_stock' => 'integer',
        'current_stock' => 'integer',
    ];

    public function kardexMovements(): HasMany
    {
        return $this->hasMany(KardexMovement::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->current_stock > 0;
    }
}