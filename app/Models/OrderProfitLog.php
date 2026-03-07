<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProfitLog extends Model
{
    protected $fillable = [
        'order_id',
        'order_item_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'unit_cost',
        'unit_profit',
        'total_price',
        'total_cost',
        'total_profit',
        'profit_margin',
        'options_snapshot',
        'cost_breakdown',
        'logged_at',
    ];

    protected $casts = [
        'unit_price'       => 'decimal:2',
        'unit_cost'        => 'decimal:2',
        'unit_profit'      => 'decimal:2',
        'total_price'      => 'decimal:2',
        'total_cost'       => 'decimal:2',
        'total_profit'     => 'decimal:2',
        'profit_margin'    => 'decimal:2',
        'options_snapshot' => 'array',
        'cost_breakdown'   => 'array',
        'logged_at'        => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
