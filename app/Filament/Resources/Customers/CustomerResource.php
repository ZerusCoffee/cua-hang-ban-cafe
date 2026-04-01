<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Relations\AddressesRelationManager;
use App\Models\Customer;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationLabel = 'Khách hàng';
    protected static string|null|\UnitEnum $navigationGroup = 'Quản lý';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Khách hàng';
    protected static ?string $pluralModelLabel = 'Khách hàng';

    protected static ?string $recordTitleAttribute = 'name';


    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedUser;


    public static function getRelations(): array
    {
        return [
            AddressesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [

            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
        ];
    }
}
