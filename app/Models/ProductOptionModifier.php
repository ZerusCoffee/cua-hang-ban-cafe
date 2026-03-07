<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOptionModifier extends Model
{
    protected $fillable = [
        'product_option_id',
        'ingredient_id',
        'delta_quantity',
    ];

    protected $casts = [
        'delta_quantity' => 'decimal:2',
    ];

    public function productOption(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function getActualQuantity(float $baseQuantity): float
    {
        return $baseQuantity + $this->delta_quantity;
    }
}
