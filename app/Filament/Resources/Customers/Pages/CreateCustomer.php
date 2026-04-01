<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected static ?string $title = 'Thêm khách hàng';

    public function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Khách hàng đã được tạo thành công';
    }
}
