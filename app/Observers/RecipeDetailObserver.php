<?php

namespace App\Observers;

use App\Jobs\RecalculateProductStock;
use App\Models\RecipeDetail;

class RecipeDetailObserver
{
    public $afterCommit = true;

    public function saved(RecipeDetail $detail): void
    {
        RecalculateProductStock::dispatchSync($detail->product);
    }

    public function deleted(RecipeDetail $detail): void
    {
        RecalculateProductStock::dispatchSync($detail->product);
    }
}
