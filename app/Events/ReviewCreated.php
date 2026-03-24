<?php

namespace App\Events;

use App\Models\Review;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;

class ReviewCreated
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(public Review $review)
    {
        //
    }
}
