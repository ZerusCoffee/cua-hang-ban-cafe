<?php

namespace App\Filament\Resources\ImportOrders;

use App\Filament\Resources\ImportOrders\Pages\CreateImportOrder;
use App\Filament\Resources\ImportOrders\Pages\EditImportOrder;
use App\Filament\Resources\ImportOrders\Pages\ListImportOrders;
use App\Filament\Resources\ImportOrders\Schemas\ImportOrderForm;
use App\Filament\Resources\ImportOrders\Tables\ImportOrdersTable;
use App\Models\ImportOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ImportOrderResource extends Resource
{
    protected static ?string $model = ImportOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Phiếu nhập hàng';

    protected static ?string $modelLabel = 'phiếu nhập';

    protected static ?string $pluralModelLabel = 'Phiếu nhập hàng';

    protected static string|null|\UnitEnum $navigationGroup = 'Nhập hàng';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return ImportOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImportOrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImportOrders::route('/'),
            'create' => CreateImportOrder::route('/create'),
            'edit' => EditImportOrder::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
