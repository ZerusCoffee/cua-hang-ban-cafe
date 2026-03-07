<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_sku',
        'price',
        'unit_cost',
        'quantity',
        'options',
        'subtotal',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'options' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function profitLog(): HasOne
    {
        return $this->hasOne(OrderProfitLog::class);
    }

    /**
     * Tính cost cho item này dựa trên RecipeDetails + delta từ options đã chọn
     * options JSON format: [{ "option_id": 1, "product_option_id": 5, ... }]
     */
    public function calculateUnitCost(): float
    {
        $product = $this->product;
        if (!$product) return 0;

        // Load recipe details
        $recipeDetails = $product->recipeDetails()->with('ingredient')->get();
        if ($recipeDetails->isEmpty()) return 0;

        // Base cost từ công thức gốc
        $ingredientAmounts = [];
        foreach ($recipeDetails as $detail) {
            $ingredientAmounts[$detail->ingredient_id] = floatval($detail->amount);
        }

        // Áp dụng delta từ options đã chọn
        $selectedOptions = $this->options ?? [];
        if (!empty($selectedOptions)) {
            $productOptionIds = collect($selectedOptions)->pluck('product_option_id')->filter()->toArray();
            if (!empty($productOptionIds)) {
                $modifiers = ProductOptionModifier::with('ingredient')
                    ->whereIn('product_option_id', $productOptionIds)
                    ->get();

                foreach ($modifiers as $modifier) {
                    $ingredientId = $modifier->ingredient_id;
                    if (isset($ingredientAmounts[$ingredientId])) {
                        $ingredientAmounts[$ingredientId] += floatval($modifier->delta_quantity);
                    } else {
                        $ingredientAmounts[$ingredientId] = floatval($modifier->delta_quantity);
                    }
                    // Đảm bảo không âm
                    $ingredientAmounts[$ingredientId] = max(0, $ingredientAmounts[$ingredientId]);
                }
            }
        }

        // Tính tổng cost
        $totalCost = 0;
        $allIngredients = Ingredient::whereIn('id', array_keys($ingredientAmounts))->get()->keyBy('id');
        foreach ($ingredientAmounts as $ingredientId => $amount) {
            $ingredient = $allIngredients->get($ingredientId);
            if ($ingredient) {
                $totalCost += $amount * floatval($ingredient->cost_price);
            }
        }

        return round($totalCost, 2);
    }

    /**
     * Trả về chi tiết breakdown nguyên liệu để lưu vào log
     */
    public function getCostBreakdown(): array
    {
        $product = $this->product;
        if (!$product) return [];

        $recipeDetails = $product->recipeDetails()->with('ingredient')->get();
        $ingredientAmounts = [];
        foreach ($recipeDetails as $detail) {
            $ingredientAmounts[$detail->ingredient_id] = [
                'name' => $detail->ingredient->name ?? '',
                'base_amount' => floatval($detail->amount),
                'delta' => 0,
                'final_amount' => floatval($detail->amount),
                'unit_cost' => floatval($detail->ingredient->cost_price ?? 0),
                'total' => 0,
            ];
        }

        $selectedOptions = $this->options ?? [];
        if (!empty($selectedOptions)) {
            $productOptionIds = collect($selectedOptions)->pluck('product_option_id')->filter()->toArray();
            if (!empty($productOptionIds)) {
                $modifiers = ProductOptionModifier::with('ingredient')
                    ->whereIn('product_option_id', $productOptionIds)
                    ->get();

                foreach ($modifiers as $modifier) {
                    $id = $modifier->ingredient_id;
                    if (isset($ingredientAmounts[$id])) {
                        $ingredientAmounts[$id]['delta'] += floatval($modifier->delta_quantity);
                        $ingredientAmounts[$id]['final_amount'] += floatval($modifier->delta_quantity);
                        $ingredientAmounts[$id]['final_amount'] = max(0, $ingredientAmounts[$id]['final_amount']);
                    }
                }
            }
        }

        foreach ($ingredientAmounts as $id => &$row) {
            $row['total'] = round($row['final_amount'] * $row['unit_cost'], 2);
        }

        return array_values($ingredientAmounts);
    }
}
