<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ImportStatsWidget;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;

class ImportStatsPage extends Dashboard
{
    use HasPageShield;
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::Document;
    protected static ?string $navigationLabel = 'Thống kê nguyên liệu';
    protected static ?string $title = 'Thống kê nguyên liệu theo số lần nhập';
    protected static string|null|\UnitEnum $navigationGroup = 'Kho Hàng';
    protected static ?string $slug = 'import-stats';
    protected static ?int $navigationSort = 12;

    protected static string $routePath = 'import-stats';

    public function getWidgets(): array
    {
        return [
            ImportStatsWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 1;
    }
}
