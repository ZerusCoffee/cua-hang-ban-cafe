<?php

namespace App\Observers;

use App\Events\OrderStatusUpdated;
use App\Jobs\RecalculateProductStock;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderProfitLog;
use App\Models\Product;
use App\Models\ProductOptionModifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderObserver
{

    public $afterCommit = true;

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Chỉ xử lý khi chuyển sang 'confirmed'
        if (!$order->wasChanged('status')) return;

        broadcast(new OrderStatusUpdated($order)); //bao doi status cho client

        if ($order->status !== 'confirmed') return;

        DB::transaction(function () use ($order) {
            $this->deductIngredients($order);
            $this->createProfitLogs($order);
        });
    }

    /**
     * Trừ nguyên liệu khi đơn được confirmed
     * Tính: RecipeDetails base + delta_quantity từ options đã chọn
     */
    protected function deductIngredients(Order $order): void
    {
        $order->load('items.product.recipeDetails.ingredient');

        foreach ($order->items as $item) {
            $product = $item->product;
            if (!$product) continue;

            // Build ingredient amounts từ công thức gốc
            $ingredientAmounts = [];
            foreach ($product->recipeDetails as $detail) {
                $ingredientAmounts[$detail->ingredient_id] =
                    ($ingredientAmounts[$detail->ingredient_id] ?? 0) + floatval($detail->amount);
            }

            // Áp dụng delta từ options đã chọn
            $selectedOptions = $item->options ?? [];
            if (!empty($selectedOptions)) {
                $productOptionIds = collect($selectedOptions)->pluck('product_option_id')->filter()->toArray();
                if (!empty($productOptionIds)) {
                    $modifiers = ProductOptionModifier::whereIn('product_option_id', $productOptionIds)->get();
                    foreach ($modifiers as $modifier) {
                        $id = $modifier->ingredient_id;
                        $ingredientAmounts[$id] = ($ingredientAmounts[$id] ?? 0) + floatval($modifier->delta_quantity);
                        $ingredientAmounts[$id] = max(0, $ingredientAmounts[$id]);
                    }
                }
            }

            // Nhân với quantity của item rồi trừ
            foreach ($ingredientAmounts as $ingredientId => $amountPerUnit) {
                $totalDeduct = $amountPerUnit * $item->quantity;
                if ($totalDeduct <= 0) continue;

                Ingredient::where('id', $ingredientId)
                    ->decrement('stock', $totalDeduct);
            }
        }

        $productIds = $order->items->pluck('product_id')->unique()->filter();
        Product::whereIn('id', $productIds)->each(function ($product) {
            RecalculateProductStock::dispatch($product);
        });

        Log::info("OrderObserver: Đã trừ nguyên liệu cho đơn #{$order->order_number}");
    }

    /**
     * Tạo profit log cho từng item khi đơn confirmed
     */
    protected function createProfitLogs(Order $order): void
    {
        $order->load('items.product');

        foreach ($order->items as $item) {
            $unitCost = $item->calculateUnitCost();
            $unitPrice = floatval($item->price);
            $unitProfit = $unitPrice - $unitCost;
            $totalPrice = $unitPrice * $item->quantity;
            $totalCost = $unitCost * $item->quantity;
            $totalProfit = $unitProfit * $item->quantity;
            $profitMargin = $unitCost > 0
                ? round(($unitProfit / $unitCost) * 100, 2)
                : 0;

            OrderProfitLog::create([
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'quantity' => $item->quantity,
                'unit_price' => $unitPrice,
                'unit_cost' => $unitCost,
                'unit_profit' => $unitProfit,
                'total_price' => $totalPrice,
                'total_cost' => $totalCost,
                'total_profit' => $totalProfit,
                'profit_margin' => $profitMargin,
                'options_snapshot' => $item->options,
                'cost_breakdown' => $item->getCostBreakdown(),
                'logged_at' => now(),
            ]);
        }

        Log::info("OrderObserver: Đã tạo profit log cho đơn #{$order->order_number}");
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
