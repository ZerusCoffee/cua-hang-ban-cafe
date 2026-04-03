<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ProductBatchTableWidget;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;

class ProductImportBatchPricePage extends Dashboard
{
    use HasPageShield;
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedChartBarSquare;
    protected static ?string $navigationLabel = 'Giá theo lô nhập';
    protected static ?string $title = 'Giá theo lô nhập';
    protected static string|null|\UnitEnum $navigationGroup = 'Sản phẩm';
    protected static string $routePath = 'product-batch-price';
    protected static ?string $slug = 'product-batch-price';
    protected static ?int $navigationSort = 20;

    public function getWidgets(): array
    {
        return [
            ProductBatchTableWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
