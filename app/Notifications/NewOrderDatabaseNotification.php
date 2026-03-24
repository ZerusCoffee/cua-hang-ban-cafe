<?php

namespace App\Notifications;

use App\Models\Order;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;


class NewOrderDatabaseNotification extends Notification
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
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Đơn hàng mới #' . $this->order->id)
            ->icon('heroicon-o-shopping-bag')
            ->iconColor('success')
            ->actions([
                Action::make('view')
                    ->label('Xem đơn')
                    ->url('/admin/orders/' . $this->order->id)
                    ->openUrlInNewTab(),
            ])
            ->getDatabaseMessage();
    }
}
