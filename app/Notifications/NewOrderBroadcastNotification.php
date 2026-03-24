<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;


class NewOrderBroadcastNotification extends Notification
{

    /**
     * Create a new notification instance.
     */
    public function __construct(public Order $order)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['broadcast'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return FilamentNotification::make()
            ->title('🛒 Đơn hàng mới #' . $this->order->id)
            ->icon('heroicon-o-shopping-bag')
            ->iconColor('success')
            ->getBroadcastMessage();
    }
}
