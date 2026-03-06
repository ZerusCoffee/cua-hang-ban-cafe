<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OptionGroup extends Model
{
    protected $fillable = [
        'name',
        'min',
        'max',
        'type',
        'is_required',
    ];

    protected $casts = [
        'min'         => 'integer',
        'max'         => 'integer',
        'is_required' => 'boolean',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(Option::class, 'group_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_options')
            ->withPivot('additional_price')
            ->withTimestamps();
    }
}
