<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Tables\CustomersTable;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    public function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }
}
