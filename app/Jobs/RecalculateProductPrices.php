<?php

namespace App\Jobs;

use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecalculateProductPrices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Ingredient $ingredient;
    protected $oldCostPrice;

    public function __construct(Ingredient $ingredient, $oldCostPrice)
    {
        $this->ingredient = $ingredient;
        $this->oldCostPrice = $oldCostPrice;
    }

    public function handle(): void
    {
        try {
            // Tìm thẳng products qua recipe_details.product_id (không qua recipes nữa)
            $products = Product::whereHas('recipeDetails', function ($query) {
                $query->where('ingredient_id', $this->ingredient->id);
            })->with('recipeDetails.ingredient')->get();

            if ($products->isEmpty()) {
                return;
            }

            $updatedCount = 0;
            foreach ($products as $product) {
                if ($this->recalculateProduct($product)) {
                    $updatedCount++;
                }
            }

            Log::info(
                'Queue: Đã cập nhật giá cho ' . $updatedCount . '/' . $products->count() .
                ' sản phẩm do nguyên liệu ' . $this->ingredient->name .
                ' thay đổi giá từ ' . number_format($this->oldCostPrice) .
                'đ -> ' . number_format($this->ingredient->cost_price) . 'đ'
            );

        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật giá sản phẩm: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function recalculateProduct(Product $product): bool
    {
        $totalCost = 0;

        foreach ($product->recipeDetails as $detail) {
            if ($detail->ingredient) {
                $totalCost += $detail->amount * $detail->ingredient->cost_price;
            }
        }

        $currentProfitRate = floatval($product->profit_rate ?? 30);
        $suggestedPrice = $totalCost > 0
            ? $totalCost * (1 + $currentProfitRate / 100)
            : 0;

        if ($product->recommended_price != $suggestedPrice) {
            $product->updateQuietly([
                'recommended_price' => $suggestedPrice,
            ]);
            return true;
        }

        return false;
    }
}
