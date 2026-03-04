<?php

namespace App\Filament\Resources\ImportOrders\Pages;

use App\Filament\Resources\ImportOrders\ImportOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateImportOrder extends CreateRecord
{
    protected static string $resource = ImportOrderResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
