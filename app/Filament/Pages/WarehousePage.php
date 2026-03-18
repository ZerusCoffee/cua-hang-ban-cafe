<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ImportStatsWidget;
use App\Filament\Widgets\StockLookupWidget;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;

class WarehousePage extends Dashboard
{
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedHome;
    protected static ?string $navigationLabel = 'Kho hàng';
    protected static ?string $title = 'Quản lý kho hàng';
    protected static string|null|\UnitEnum $navigationGroup = 'Nhập hàng';
    protected static ?string $slug = 'warehouse';
    protected static ?int $navigationSort = 12;

    protected static string $routePath = 'warehouse';

    public function getWidgets(): array
    {
        return [
            StockLookupWidget::class,
            ImportStatsWidget::class
        ];
    }

    public function getColumns(): int | array
    {
        return 1;
    }
}
