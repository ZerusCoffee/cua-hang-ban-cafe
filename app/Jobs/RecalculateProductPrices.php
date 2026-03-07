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

        // Đọc thẳng recipeDetails từ product, không qua recipe
        foreach ($product->recipeDetails as $detail) {
            if ($detail->ingredient) {
                $totalCost += $detail->amount * $detail->ingredient->cost_price;
            }
        }
        $totalCost = round($totalCost, 2);

        $currentProfitRate = floatval($product->profit_rate ?? 30);

        if ($totalCost > 0) {
            $suggestedPrice = (int) ceil(($totalCost * (1 + $currentProfitRate / 100)) / 1000) * 1000;
            $actualProfitRate = round(($suggestedPrice - $totalCost) / $totalCost * 100, 2);
        } else {
            $suggestedPrice = 0;
            $actualProfitRate = 0;
        }

        if ($product->recommended_price != $suggestedPrice || $product->profit_rate != $actualProfitRate) {
            $product->updateQuietly([
                'recommended_price' => $suggestedPrice,
                'profit_rate'       => $actualProfitRate,
            ]);
            return true;
        }

        return false;
    }
}
