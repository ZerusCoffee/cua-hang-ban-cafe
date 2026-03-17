<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\StockLookupWidget;
use Filament\Pages\Dashboard;
use App\Filament\Widgets\StockInventoryWidget;

class WarehousePage extends Dashboard
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Kho hàng';
    protected static ?string $title = 'Quản lý kho hàng';
    protected static ?string $slug = 'warehouse';
    protected static ?int $navigationSort = 1;

    protected static string $routePath = 'warehouse';

    public function getWidgets(): array
    {
        return [
            StockLookupWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 1;
    }
}
