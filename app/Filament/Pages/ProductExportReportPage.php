<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ProductExportReportWidget;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;

class ProductExportReportPage extends Dashboard
{
    use HasPageShield;
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedDocumentChartBar;
    protected static ?string $navigationLabel = 'Báo cáo xuất SP';
    protected static ?string $title = 'Báo cáo xuất sản phẩm';
    protected static string|null|\UnitEnum $navigationGroup = 'Bán hàng';
    protected static ?string $slug = 'product-report';
    protected static ?int $navigationSort = 20;

    protected static string $routePath = 'product-report';


    public function getWidgets(): array
    {
        return [
            ProductExportReportWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 1;
    }
}
