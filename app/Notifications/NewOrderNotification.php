<?php

namespace App\Notifications;

use App\Models\Order;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;


class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
        return ['broadcast', 'database'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return FilamentNotification::make()
            ->title('🛒 Đơn hàng mới #' . $this->order->id)
            ->icon('heroicon-o-shopping-bag')
            ->iconColor('success')
            ->getBroadcastMessage();
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
