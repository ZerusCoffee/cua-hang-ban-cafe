<?php

namespace App\Observers;

use App\Jobs\RecalculateProductPrices;
use App\Models\ImportOrderDetail;
use App\Models\Ingredient;
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
            $this->handleIngredientPriceChange($importOrderDetail->ingredient_id);
        }
    }

    /**
     * Handle the ImportOrderDetail "updated" event.
     */
    public function updated(ImportOrderDetail $importOrderDetail): void
    {
        if ($importOrderDetail->wasChanged('unit_price') && $importOrderDetail->importOrder->status === 'completed') {
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
        if (!$ingredient) {
            return;
        }

        // Lưu giá cũ
        $oldCostPrice = $ingredient->cost_price;

        // Cập nhật giá mới từ lô nhập gần nhất
        $this->updateIngredientCostPrice($ingredient);

        // Nếu giá thay đổi thì dispatch job
        if ($oldCostPrice != $ingredient->cost_price) {
            RecalculateProductPrices::dispatch($ingredient, $oldCostPrice);

            Log::info("ImportOrderDetailObserver: Đã dispatch job cập nhật sản phẩm cho nguyên liệu #{$ingredientId}");
        }
    }

    protected function updateIngredientCostPrice(Ingredient $ingredient): void
    {
        // Lấy giá nhập gần nhất từ import_order_details
        $latestPrice = ImportOrderDetail::query()
            ->where('ingredient_id', $ingredient->id)
            ->whereHas('importOrder', fn($q) => $q->where('status', 'completed'))
            ->latest()
            ->value('unit_price');

        if ($latestPrice && $ingredient->cost_price != $latestPrice) {
            $oldPrice = $ingredient->cost_price;
            $ingredient->cost_price = $latestPrice;
            $ingredient->saveQuietly(); // Save nhưng không trigger observer để tránh loop

            Log::info("ImportOrderDetailObserver: Đã cập nhật giá nguyên liệu #{$ingredient->id}: {$oldPrice} -> {$latestPrice}");
        }
    }
}
