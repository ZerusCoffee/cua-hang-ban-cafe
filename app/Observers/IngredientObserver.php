<?php

namespace App\Observers;

use App\Jobs\RecalculateProductPrices;
use App\Models\Ingredient;
use App\Models\Product;

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
            $oldCostPrice = $ingredient->getOriginal('cost_price');
            RecalculateProductPrices::dispatchSync($ingredient, $oldCostPrice);
        }
    }

    public function deleting(Ingredient $ingredient): void
    {
        $productIds = $ingredient->recipeDetails()
            ->pluck('product_id')
            ->unique()
            ->filter();

        Product::whereIn('id', $productIds)->update(['is_active' => false]);
    }

}
