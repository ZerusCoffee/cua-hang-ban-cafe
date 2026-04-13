<?php

namespace App\Providers;

use App\Models\Ingredient;
use App\Models\Order;
use App\Models\RecipeDetail;
use App\Observers\IngredientObserver;
use App\Observers\OrderObserver;
use App\Observers\RecipeDetailObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Ingredient::observe(IngredientObserver::class);
        RecipeDetail::observe(RecipeDetailObserver::class);
        Order::observe(OrderObserver::class);
    }
}
