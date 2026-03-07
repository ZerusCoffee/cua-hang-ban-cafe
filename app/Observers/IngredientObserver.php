<?php

namespace App\Observers;

use App\Jobs\RecalculateProductPrices;
use App\Models\Ingredient;

class IngredientObserver
{

    public $afterCommit = true;

    /**
     * Handle the Ingredient "created" event.
     */
    public function created(Ingredient $ingredient): void
    {
        //
    }

    /**
     * Handle the Ingredient "updated" event.
     */
    public function updated(Ingredient $ingredient): void
    {
        if ($ingredient->wasChanged('cost_price')) {
            // Sử dụng queue để xử lý bất đồng bộ
            $oldCostPrice = $ingredient->getOriginal('cost_price');
            RecalculateProductPrices::dispatch($ingredient, $oldCostPrice);
        }
    }


}
