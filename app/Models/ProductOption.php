<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOption extends Model
{
    protected $table = 'product_options';

    protected $fillable = [
        'product_id',
        'option_id',
        'additional_price',
    ];

    protected $casts = [
        'additional_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'option_id');
    }

     // Helper để lấy giá trị option
    public function getOptionValueAttribute()
    {
        return $this->option?->value;
    }

    // Helper để lấy tên nhóm
    public function getGroupNameAttribute()
    {
        return $this->option?->group?->name;
    }
}
