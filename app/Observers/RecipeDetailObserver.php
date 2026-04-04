<?php

namespace App\Observers;

use App\Jobs\RecalculateProductStock;
use App\Models\RecipeDetail;

class RecipeDetailObserver
{
    public function saved(RecipeDetail $detail): void
    {
        RecalculateProductStock::dispatch($detail->product);
    }

    public function deleted(RecipeDetail $detail): void
    {
        RecalculateProductStock::dispatch($detail->product);
    }
}
