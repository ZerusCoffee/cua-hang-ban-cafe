<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Schemas\CustomerInfolist;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset_password')
                ->label('Đặt lại mật khẩu')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->modalHeading('Đặt lại mật khẩu')
                ->modalDescription('Nhập mật khẩu mới cho khách hàng. Tất cả phiên đăng nhập hiện tại sẽ bị đăng xuất.')
                ->modalWidth('md')
                ->form([
                    TextInput::make('password')
                        ->label('Mật khẩu mới')
                        ->password()
                        ->revealable()
                        ->required()
                        ->minLength(8),

                    TextInput::make('password_confirmation')
                        ->label('Xác nhận mật khẩu')
                        ->password()
                        ->revealable()
                        ->required()
                        ->same('password'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'password' => Hash::make($data['password']),
                    ]);

                    // Buộc đăng xuất tất cả thiết bị
                    $this->record->tokens()->delete();

                    Notification::make()
                        ->title('Đặt lại mật khẩu thành công')
                        ->success()
                        ->send();
                }),

            Action::make('toggle_lock')
                ->label(fn() => $this->record->is_locked ? 'Mở khoá tài khoản' : 'Khoá tài khoản')
                ->icon(fn() => $this->record->is_locked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                ->color(fn() => $this->record->is_locked ? 'success' : 'danger')
                ->requiresConfirmation()
                ->action(function () {
                    $locking = !$this->record->is_locked;

                    $this->record->update(['is_locked' => $locking]);

                    if ($locking) {
                        $this->record->tokens()->delete();
                    }

                    $this->refreshFormData(['is_locked']);
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return CustomerInfolist::configure($schema);
    }
}
