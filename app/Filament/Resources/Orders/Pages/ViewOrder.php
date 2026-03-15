<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Schemas\OrderInfolist;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirm')
                ->label('Xác nhận đơn')
                ->icon('heroicon-o-check-circle')
                ->color('info')
                ->visible(fn() => $this->record->status === 'pending')
                ->requiresConfirmation()
                ->action(fn() => $this->record->updateStatus('confirmed', 'Xác nhận bởi admin', auth()->id())),

            Action::make('deliver')
                ->label('Đánh dấu đã giao')
                ->icon('heroicon-o-truck')
                ->color('success')
                ->visible(fn() => $this->record->status === 'confirmed')
                ->requiresConfirmation()
                ->action(fn() => $this->record->updateStatus('delivered', 'Giao hàng thành công', auth()->id())),

            Action::make('cancel')
                ->label('Huỷ đơn')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn() => in_array($this->record->status, ['pending', 'confirmed']))
                ->requiresConfirmation()
                ->form([
                    Textarea::make('admin_notes')->label('Lý do huỷ')->required(),
                ])
                ->action(fn(array $data) => $this->record->updateStatus('cancelled', $data['admin_notes'], auth()->id())),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
    }
}
