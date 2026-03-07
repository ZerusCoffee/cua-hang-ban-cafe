<?php

namespace App\Jobs;

use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecalculateProductPrices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Ingredient $ingredient;
    protected $oldCostPrice;

    /**
     * Create a new job instance.
     */
    public function __construct(Ingredient $ingredient, $oldCostPrice)
    {
        $this->ingredient = $ingredient;
        $this->oldCostPrice = $oldCostPrice;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Tìm tất cả products sử dụng ingredient này
            $recipeIds = DB::table('recipe_details')
                ->where('ingredient_id', $this->ingredient->id)
                ->distinct()
                ->pluck('recipe_id');

            if ($recipeIds->isEmpty()) {
                return;
            }

            $products = Product::whereIn('id', function ($query) use ($recipeIds) {
                $query->select('product_id')
                    ->from('recipes')
                    ->whereIn('id', $recipeIds);
            })->with('recipe.recipeDetails.ingredient')->get();

            $updatedCount = 0;
            foreach ($products as $product) {
                if ($this->recalculateProduct($product)) {
                    $updatedCount++;
                }
            }

            Log::info('Queue: Đã cập nhật giá cho ' . $updatedCount . '/' . $products->count() . ' sản phẩm do nguyên liệu ' . $this->ingredient->name . ' thay đổi giá từ ' . number_format($this->oldCostPrice) . 'đ -> ' . number_format($this->ingredient->cost_price) . 'đ');

        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật giá sản phẩm: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function recalculateProduct(Product $product): bool
    {
        if (!$product->recipe) {
            return false;
        }

        $totalCost = 0;
        foreach ($product->recipe->recipeDetails as $detail) {
            if ($detail->ingredient) {
                $totalCost += $detail->amount * $detail->ingredient->cost_price;
            }
        }
        $totalCost = round($totalCost, 2);

        $currentProfitRate = $product->profit_rate ?? 30;

        if ($totalCost > 0) {
            $suggestedPrice = (int) ceil(($totalCost * (1 + $currentProfitRate / 100)) / 1000) * 1000;
            $actualProfitRate = round(($suggestedPrice - $totalCost) / $totalCost * 100, 2);
        } else {
            $suggestedPrice = 0;
            $actualProfitRate = 0;
        }

        // Chỉ update nếu có thay đổi
        if ($product->recommended_price != $suggestedPrice || $product->profit_rate != $actualProfitRate) {
            $product->updateQuietly([
                'recommended_price' => $suggestedPrice,
                'profit_rate' => $actualProfitRate,
            ]);
            return true;
        }

        return false;
    }
}
