<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientImportLog extends Model
{
    protected $fillable = [
        'ingredient_id',
        'import_order_id',
        'import_order_code',
        'quantity',
        'stock_before',
        'stock_after',
        'unit_price',
        'cost_price_before',
        'cost_price_after',
        'imported_at',
    ];

    protected $casts = [
        'quantity'           => 'decimal:3',
        'stock_before'       => 'decimal:3',
        'stock_after'        => 'decimal:3',
        'unit_price'         => 'decimal:2',
        'cost_price_before'  => 'decimal:2',
        'cost_price_after'   => 'decimal:2',
        'imported_at'        => 'datetime',
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function importOrder(): BelongsTo
    {
        return $this->belongsTo(ImportOrder::class);
    }
}
