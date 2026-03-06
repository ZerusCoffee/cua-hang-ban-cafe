<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOptionModifier extends Model
{
    protected $fillable = [
        'product_id',
        'ingredient_id',
        'deltaQuantity',
    ];

    protected $casts = [
        'deltaQuantity' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function getActualQuantity(float $baseQuantity): float
    {
        return $baseQuantity + $this->deltaQuantity;
    }

    /**
     * Tính giá cuối cùng khi chọn option
     */
    public function calculateFinalPrice(float $basePrice, array $selectedOptionGroupIds): float
    {
        $additionalPrice = $this->optionGroups()
            ->whereIn('option_group_id', $selectedOptionGroupIds)
            ->sum('additional_price');

        return $basePrice + $additionalPrice;
    }

    public function calculateRequiredIngredients(array $selectedOptions): array
    {
        $ingredients = [];

        foreach ($this->recipeDetails as $detail) {
            $baseAmount = $detail->amount;
            $modifier = $this->optionModifiers()
                ->where('ingredient_id', $detail->ingredient_id)
                ->first();

            $finalAmount = $modifier
                ? $baseAmount + $modifier->deltaQuantity
                : $baseAmount;

            $ingredients[$detail->ingredient_id] = [
                'ingredient' => $detail->ingredient,
                'amount' => $finalAmount
            ];
        }

        return $ingredients;
    }

}
