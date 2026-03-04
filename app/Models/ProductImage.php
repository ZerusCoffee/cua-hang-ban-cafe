<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'image_path',
        'alt_text',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::created(function (ProductImage $image) {
            $count = static::where('product_id', $image->product_id)->count();
            if ($count === 1) {
                $image->updateQuietly(['is_primary' => true]);
            }
        });
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
