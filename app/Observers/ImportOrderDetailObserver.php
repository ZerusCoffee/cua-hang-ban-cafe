<?php

namespace App\Observers;

use App\Jobs\RecalculateProductPrices;
use App\Jobs\RecalculateProductStock;
use App\Models\ImportOrderDetail;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\RecipeDetail;
use Illuminate\Support\Facades\Log;

class ImportOrderDetailObserver
{
    public $afterCommit = true;

    /**
     * Handle the ImportOrderDetail "created" event.
     */
    public function created(ImportOrderDetail $importOrderDetail): void
    {
        if ($importOrderDetail->importOrder->status === 'completed') {

            Log::info('ImportObserver created fired, status=' . $importOrderDetail->importOrder->status);

            $this->handleIngredientPriceChange($importOrderDetail->ingredient_id);
        }
    }

    /**
     * Handle the ImportOrderDetail "updated" event.
     */
    public function updated(ImportOrderDetail $importOrderDetail): void
    {
        if ($importOrderDetail->wasChanged('unit_price') && $importOrderDetail->importOrder->status === 'completed') {
            Log::info('ImportObserver updated fired, status=' . $importOrderDetail->importOrder->status);
            $this->handleIngredientPriceChange($importOrderDetail->ingredient_id);
        }
    }

    /**
     * Handle the ImportOrderDetail "deleted" event.
     */
    public function deleted(ImportOrderDetail $importOrderDetail): void
    {
        if ($importOrderDetail->importOrder->status === 'completed') {
            $this->handleIngredientPriceChange($importOrderDetail->ingredient_id);
        }
    }

    protected function handleIngredientPriceChange(int $ingredientId): void
    {
        $ingredient = Ingredient::find($ingredientId);
        if (!$ingredient) return;

        // Dispatch stock trước
        $productIds = RecipeDetail::where('ingredient_id', $ingredientId)
            ->pluck('product_id')
            ->unique();

        Log::info('ImportObserver: Dispatching stock cho ' . $productIds->count() . ' sản phẩm, ingredientId=' . $ingredientId);

        $products = Product::whereIn('id', $productIds)->get();
        foreach ($products as $product) {
            RecalculateProductStock::dispatch($product);
        }

        // Sau đó mới tính giá
        $oldCostPrice = $ingredient->cost_price;
        $this->updateIngredientCostPrice($ingredient);

        if ($oldCostPrice != $ingredient->cost_price) {
            RecalculateProductPrices::dispatch($ingredient, $oldCostPrice);
        }
    }

    protected function updateIngredientCostPrice(Ingredient $ingredient): void
    {
        $latestPrice = ImportOrderDetail::query()
            ->where('ingredient_id', $ingredient->id)
            ->whereHas('importOrder', fn($q) => $q->where('status', 'completed'))
            ->latest()
            ->value('unit_price');

        if ($latestPrice && $ingredient->cost_price != $latestPrice) {
            $oldPrice = $ingredient->cost_price;
            $ingredient->cost_price = $latestPrice;
            $ingredient->saveQuietly();

            Log::info("ImportOrderDetailObserver: Đã cập nhật giá nguyên liệu #{$ingredient->id}: {$oldPrice} -> {$latestPrice}");
        }
    }
}
