<?php

namespace App\Notifications;

use App\Models\Review;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReviewBroadcastNotification extends Notification
{

    /**
     * Create a new notification instance.
     */
    public function __construct(public Review $review)
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
            ->title('Đánh giá mới #' . $this->review->id)
            ->body('Sản phẩm ' . $this->review->product()->name)
            ->icon(Heroicon::OutlinedStar)
            ->iconColor('success')
            ->getBroadcastMessage();
    }
}
