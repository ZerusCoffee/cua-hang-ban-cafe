<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Schemas\CustomerInfolist;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggle_lock')
                ->label(fn() => $this->record->is_locked ? 'Mở khoá tài khoản' : 'Khoá tài khoản')
                ->icon(fn() => $this->record->is_locked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                ->color(fn() => $this->record->is_locked ? 'success' : 'danger')
                ->requiresConfirmation()
                ->action(function () {
                    $locking = !$this->record->is_locked;

                    $this->record->update(['is_locked' => $locking]);

                    // Khoá thì xóa hết token → fe tự đá ra ngoài
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
