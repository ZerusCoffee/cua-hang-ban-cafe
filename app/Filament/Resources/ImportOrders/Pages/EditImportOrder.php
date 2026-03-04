<?php

namespace App\Filament\Resources\ImportOrders\Pages;

use App\Filament\Resources\ImportOrders\ImportOrderResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditImportOrder extends EditRecord
{
    protected static string $resource = ImportOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('complete')
                ->label('Hoàn thành phiếu')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Xác nhận hoàn thành phiếu nhập')
                ->modalDescription('Sau khi hoàn thành, tồn kho sẽ được cập nhật và phiếu không thể sửa. Bạn chắc chắn?')
                ->modalSubmitActionLabel('Hoàn thành')
                ->hidden(fn() => $this->record->status === 'completed')
                ->action(function () {
                    $this->record->load('details');
                    $this->record->complete();

                    Notification::make()
                        ->title('Hoàn thành phiếu nhập thành công!')
                        ->body('Tồn kho nguyên liệu đã được cập nhật.')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            DeleteAction::make()
                ->hidden(fn() => $this->record->status === 'completed') //complete đéo cho xóa
                ->action(fn($record) => $record->safeDelete())
                ->requiresConfirmation(),

            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
