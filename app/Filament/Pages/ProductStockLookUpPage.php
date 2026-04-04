<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ProductStockLookupWidget;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;

class ProductStockLookUpPage extends Dashboard
{
    use HasPageShield;
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::CircleStack;
    protected static ?string $navigationLabel = 'Thống kê sản phẩm';
    protected static ?string $title = 'Thống kê sản phẩm theo thời điểm';
    protected static string|null|\UnitEnum $navigationGroup = 'Kho Hàng';
    protected static ?string $slug = 'products-stock-lookup';
    protected static ?int $navigationSort = 12;

    protected static string $routePath = 'products-stock-lookup';

    public function getWidgets(): array
    {
        return [
            ProductStockLookupWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 1;
    }
}
