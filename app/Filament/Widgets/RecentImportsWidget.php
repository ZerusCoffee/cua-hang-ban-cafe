<?php

namespace App\Filament\Widgets;

use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use App\Models\IngredientImportLog;

class RecentImportsWidget extends TableWidget
{
    protected static ?string $heading = 'Nhập hàng gần đây';

    protected static ?int $sort = 3;

    protected function getTableQuery(): Builder
    {
        return IngredientImportLog::with('ingredient')
            ->latest('imported_at')
            ->limit(5);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getTableQuery())
            ->columns([
                TextColumn::make('ingredient.name')
                    ->label('Nguyên liệu')
                    ->searchable(),

                TextColumn::make('import_order_code')
                    ->label('Mã phiếu')
                    ->badge()
                    ->color('info'),

                TextColumn::make('quantity')
                    ->label('Số lượng')
                    ->formatStateUsing(fn($state, $record) =>
                        rtrim(rtrim(number_format((float)$state, 3, '.', ''), '0'), '.')
                        . ' ' . ($record->ingredient?->unit?->symbol ?? '')
                    )
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('unit_price')
                    ->label('Đơn giá')
                    ->money('VND'),

                TextColumn::make('imported_at')
                    ->label('Thời gian')
                    ->dateTime('H:i d/m/Y')
                    ->sortable(),
            ])
            ->searchable() // Hiện search bar
            ->searchDebounce(500)
            ->headerActions([]) // Search tự lên header cùng title
            ->paginated(false);
    }
}
