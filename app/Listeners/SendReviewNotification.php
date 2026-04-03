<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\NewReviewBroadcastNotification;
use App\Notifications\NewReviewDatabaseNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendReviewNotification implements ShouldQueue
{
    use Queueable;
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
         $admins = User::role(['super_admin', 'manager', 'sales'])->get();

        foreach ($admins as $admin) {
            $admin->notify(new NewReviewBroadcastNotification($event->review));
        }
    }
}
