<?php

namespace App\Providers;

use App\Models\ImportOrderDetail;
use App\Models\Ingredient;
use App\Models\Order;
use App\Observers\ImportOrderDetailObserver;
use App\Observers\IngredientObserver;
use App\Observers\OrderObserver;
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
        ImportOrderDetail::observe(ImportOrderDetailObserver::class);
        Order::observe(OrderObserver::class);
    }
}
