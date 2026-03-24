<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

class OrderCreated
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(public Order $order)
    {
        //
    }
}
