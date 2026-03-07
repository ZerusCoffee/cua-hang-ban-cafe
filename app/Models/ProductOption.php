<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    // Modifiers lồng trong product_option này
    public function modifiers(): HasMany
    {
        return $this->hasMany(ProductOptionModifier::class, 'product_option_id');
    }

    public function getOptionValueAttribute(): ?string
    {
        return $this->option?->value;
    }

    public function getGroupNameAttribute(): ?string
    {
        return $this->option?->group?->name;
    }
}
