<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\StockLookupWidget;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;

class IngredientStockLookupPage extends Dashboard
{
    use HasPageShield;
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::PaperClip;
    protected static ?string $navigationLabel = 'Tồn kho nguyên liệu';
    protected static ?string $title = 'Tra cứu tồn kho nguyên liệu';
    protected static string|null|\UnitEnum $navigationGroup = 'Kho Hàng';
    protected static ?string $slug = 'ingredient-stock-lookup';
    protected static ?int $navigationSort = 12;

    protected static string $routePath = 'ingredient-stock-lookup';

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
