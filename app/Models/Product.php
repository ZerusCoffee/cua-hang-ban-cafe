<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'recommended_price', // Giá bán đề xuất
        'profit_rate', // tỉ lệ lợi nhuận
        'short_description',
        'description',
        'is_featured',
        'is_active',
        'view_count',
    ];


    protected $casts = [
        'recommended_price' => 'decimal:2',
        'profit_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'view_count' => 'integer',
    ];

    /**
     * Tính giá cost từ recipe:
     * Σ (amount × unit_price của nguyên liệu trong lô nhập gần nhất)
     */
    public function getCostPriceAttribute(): float
    {
        $recipe = $this->recipe?->load('recipeDetails.ingredient');

        if (! $recipe) {
            return 0;
        }

        return $recipe->recipeDetails->sum(function ($detail) {
            // Lấy giá nhập gần nhất của nguyên liệu từ import_order_details
            $latestPrice = \App\Models\ImportOrderDetail::query()
                ->where('ingredient_id', $detail->ingredient_id)
                ->whereHas('importOrder', fn ($q) => $q->where('status', 'completed'))
                ->latest()
                ->value('unit_price') ?? $detail->ingredient?->price ?? 0;

            return $detail->amount * $latestPrice;
        });
    }


    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }

            if (empty($product->sku)) {
                $product->sku = 'SP-TMP-' . time();  // tạm
            }
        });

        static::created(function (Product $product) {
            if (str_starts_with($product->sku, 'SP-TMP-')) {
                $product->updateQuietly([
                    'sku' => 'SP-' . str_pad($product->id, 3, '0', STR_PAD_LEFT),
                ]);
            }
        });

        static::updating(function (Product $product) {
            if ($product->isDirty('name') && !$product->isDirty('slug')) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function recipe(): HasOne
    {
        return $this->hasOne(Recipe::class);
    }
}
