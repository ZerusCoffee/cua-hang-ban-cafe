<?php

namespace App\Providers;

use App\Events\OrderCreated;
use App\Events\ReviewCreated;
use App\Listeners\SendOrderNotifications;
use App\Listeners\SendReviewNotification;
use App\Models\ImportOrderDetail;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\RecipeDetail;
use App\Observers\ImportOrderDetailObserver;
use App\Observers\IngredientObserver;
use App\Observers\OrderObserver;
use App\Observers\RecipeDetailObserver;
use Illuminate\Support\Facades\Event;
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
        RecipeDetail::observe(RecipeDetailObserver::class);
        Event::listen(
            OrderCreated::class,
            SendOrderNotifications::class,
        );
        Event::listen(
            ReviewCreated::class,
            SendReviewNotification::class
        );
    }
}
