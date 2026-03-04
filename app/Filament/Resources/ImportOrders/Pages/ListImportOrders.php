<?php

namespace App\Filament\Resources\ImportOrders\Pages;

use App\Filament\Resources\ImportOrders\ImportOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListImportOrders extends ListRecords
{
    protected static string $resource = ImportOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
