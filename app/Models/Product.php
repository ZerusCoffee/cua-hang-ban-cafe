<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @method static \Illuminate\Database\Eloquent\Builder active()
 */
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

    protected $with = [
        'category',
        'primaryImage',
    ];

    protected $casts = [
        'recommended_price' => 'decimal:2',
        'profit_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'view_count' => 'integer',
    ];

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function featured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function recipeDetails(): HasMany
    {
        return $this->hasMany(RecipeDetail::class);
    }


    /**
     * Tính giá cost từ recipe:
     * Σ (amount × unit_price của nguyên liệu trong lô nhập gần nhất)
     */
    public function getCostPriceAttribute(): float
    {
        $recipeDetails = $this->recipeDetails()->with('ingredient')->get();

        if ($recipeDetails->isEmpty()) {
            return 0;
        }

        return $recipeDetails->sum(function ($detail) {
            $latestPrice = ImportOrderDetail::query()
                ->where('ingredient_id', $detail->ingredient_id)
                ->whereHas('importOrder', fn($q) => $q->where('status', 'completed'))
                ->latest()
                ->value('unit_price') ?? $detail->ingredient?->cost_price ?? 0;

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

        static::deleting(function (Product $product) {
            $product->recipeDetails()->delete();
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

    public function productOptions(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(Option::class, 'product_options')
            ->withPivot('additional_price')
            ->withTimestamps();
    }

    public function optionModifiers(): HasMany
    {
        return $this->hasMany(ProductOptionModifier::class);
    }

    /**
     * Lấy giá của 1 option cụ thể
     */
    public function getOptionPrice(int $optionId): float
    {
        $productOption = $this->productOptions()
            ->where('option_id', $optionId)
            ->first();

        return $productOption?->additional_price ?? 0;
    }

    /**
     * Lấy tất cả option theo nhóm
     */
    public function getOptionsByGroup(): array
    {
        $result = [];

        foreach ($this->productOptions as $productOption) {
            $groupName = $productOption->option->group->name;
            $optionValue = $productOption->option->value;

            if (!isset($result[$groupName])) {
                $result[$groupName] = [
                    'group' => $productOption->option->group,
                    'options' => []
                ];
            }

            $result[$groupName]['options'][] = [
                'id' => $productOption->option_id,
                'value' => $optionValue,
                'price' => $productOption->additional_price
            ];
        }

        return $result;
    }
}
