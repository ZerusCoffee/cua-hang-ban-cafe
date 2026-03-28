<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\User;
use App\Notifications\NewOrderBroadcastNotification;
use App\Notifications\NewOrderDatabaseNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOrderNotifications implements ShouldQueue
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
    public function handle(OrderCreated $event): void
    {
        $admins = User::role(['super_admin'])->get();

        foreach ($admins as $admin) {
            $admin->notify(new NewOrderBroadcastNotification($event->order));
            $admin->notify(new NewOrderDatabaseNotification($event->order));
        }
    }
}
