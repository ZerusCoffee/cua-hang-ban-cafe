<?php

namespace App\Notifications;

use App\Models\Review;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewReviewDatabaseNotification extends Notification
{
    use Queueable;

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
        return ['mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Đánh giá mới #' . $this->review->id)
            ->body('Sản phẩm ' . $this->review->product()->name)
            ->icon(Heroicon::OutlinedStar)
            ->iconColor('success')
            ->actions([
                Action::make('view')
                    ->label('Xem đánh giá')
                    ->url('/admin/reviews/' . $this->review->id)
                    ->openUrlInNewTab(),
            ])
            ->getDatabaseMessage();
    }
}
