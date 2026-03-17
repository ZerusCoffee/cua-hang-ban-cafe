<?php

namespace App\Filament\Widgets;

use App\Models\OrderProfitLog;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TopProductsWidget extends TableWidget
{
    protected static ?string $heading = 'Sản phẩm bán chạy hôm nay';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        $sub = OrderProfitLog::whereDate('logged_at', today())
            ->select([
                DB::raw('MIN(id) as id'),
                'product_name',
                'product_sku',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total_price) as total_revenue'),
                DB::raw('SUM(total_profit) as total_profit'),
            ])
            ->groupBy('product_name', 'product_sku');

        return OrderProfitLog::fromSub($sub, 'order_profit_logs')
            ->reorder()
            ->orderByDesc('total_quantity')
            ->orderBy('product_name');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getTableQuery())
            ->columns([
                TextColumn::make('product_name')
                    ->label('Sản phẩm')
                    ->searchable()
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('product_sku')
                    ->label('Mã SP')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('total_quantity')
                    ->label('Số lượng')
                    ->formatStateUsing(fn($state) =>
                        rtrim(rtrim(number_format((float)$state, 3, '.', ''), '0'), '.')
                    )
                    ->sortable()
                    ->alignCenter()
                    ->color('warning'),

                TextColumn::make('total_revenue')
                    ->label('Doanh thu')
                    ->money('VND')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('total_profit')
                    ->label('Lợi nhuận')
                    ->money('VND')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('profit_margin')
                    ->label('Biên LN')
                    ->state(function ($record) {
                        if (!$record->total_revenue) return '0%';
                        return round(($record->total_profit / $record->total_revenue) * 100, 1) . '%';
                    })
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        (float) $state < 30 => 'danger',
                        (float) $state < 50 => 'warning',
                        default             => 'success',
                    }),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([])
            ->bulkActions([])
            ->paginated(false);
    }
}
