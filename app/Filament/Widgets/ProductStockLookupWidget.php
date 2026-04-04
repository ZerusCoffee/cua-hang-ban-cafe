<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\ProductStockLog;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

class ProductStockLookupWidget extends BaseWidget
{
    protected static ?string $heading = 'Tồn kho sản phẩm';
    protected int|string|array $columnSpan = 'full';
    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        return $table
            ->query(Product::query()->with(['primaryImage', 'recipeDetails.ingredient', 'latestStockLog']))
            ->columns([
                ImageColumn::make('primaryImage.image_path')
                    ->label('Ảnh')
                    ->disk('public')
                    ->square()
                    ->size(40)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover']),

                TextColumn::make('name')
                    ->label('Tên sản phẩm')
                    ->searchable()
                    ->weight(FontWeight::Medium)
                    ->sortable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->badge()
                    ->color('gray')
                    ->fontFamily(FontFamily::Mono),

                TextColumn::make('latestStockLog.max_quantity')
                    ->label('Có thể bán')
                    ->default(0)
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state > 10  => 'success',
                        $state > 0   => 'warning',
                        default      => 'danger',
                    })
                    ->alignEnd(),

                TextColumn::make('latestStockLog.logged_at')
                    ->label('Cập nhật lúc')
                    ->dateTime('d/m/Y H:i')
                    ->color('gray')
                    ->alignEnd(),
            ])
            ->actions([
                Action::make('view_stock_logs')
                    ->label('Lịch sử')
                    ->icon('heroicon-o-chart-bar')
                    ->color('gray')
                    ->slideOver()
                    ->infolist(function (Product $record) {
                        $logs = $this->getStockLogs($record);

                        return [
                            Grid::make(2)
                                ->schema([
                                    Section::make('Thông tin sản phẩm')
                                        ->schema([
                                            TextEntry::make('name')
                                                ->label('Tên sản phẩm')
                                                ->state($record->name),
                                            TextEntry::make('sku')
                                                ->label('SKU')
                                                ->state($record->sku)
                                                ->badge()
                                                ->color('gray'),
                                            TextEntry::make('max_quantity')
                                                ->label('Có thể bán hiện tại')
                                                ->state($record->latestStockLog?->max_quantity ?? 0)
                                                ->badge()
                                                ->color(fn ($state): string => match (true) {
                                                    $state > 10  => 'success',
                                                    $state > 0   => 'warning',
                                                    default      => 'danger',
                                                }),
                                            TextEntry::make('last_calculated')
                                                ->label('Tính lần cuối')
                                                ->state(
                                                    $record->latestStockLog?->logged_at
                                                        ?->format('d/m/Y H:i') ?? 'Chưa có'
                                                ),
                                        ])->columns(2),
                                ]),

                            Section::make('Lịch sử tồn kho')
                                ->schema(
                                    $logs->map(fn ($item, $index) => Grid::make(3)
                                        ->schema([
                                            TextEntry::make("index_{$index}")
                                                ->label('#')
                                                ->state($item['index'])
                                                ->badge()
                                                ->color($item['is_latest'] ? 'success' : 'gray'),
                                            TextEntry::make("qty_{$index}")
                                                ->label('Số lượng có thể bán')
                                                ->state($item['max_quantity'] . ' sp')
                                                ->badge()
                                                ->color(match (true) {
                                                    $item['max_quantity'] > 10 => 'success',
                                                    $item['max_quantity'] > 0  => 'warning',
                                                    default                    => 'danger',
                                                }),
                                            TextEntry::make("logged_at_{$index}")
                                                ->label('Thời điểm')
                                                ->state($item['logged_at']),
                                        ])
                                    )->toArray()
                                )
                                ->columns(1)
                                ->visible($logs->isNotEmpty()),

                            Section::make('')
                                ->schema([
                                    TextEntry::make('empty')
                                        ->hiddenLabel()
                                        ->state('Chưa có lịch sử tồn kho nào.'),
                                ])
                                ->visible($logs->isEmpty()),
                        ];
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng')
                    ->modalWidth('4xl'),
            ])
            ->defaultSort('name')
            ->paginated([10, 25, 50])
            ->striped();
    }

    private function getStockLogs(Product $record): Collection
    {
        $logs = ProductStockLog::where('product_id', $record->id)
            ->orderByDesc('logged_at')
            ->get()
            ->values();

        if ($logs->isEmpty()) {
            return collect();
        }

        return $logs->map(fn ($log, $index) => [
            'index'        => $logs->count() - $index,  // đánh số từ lần 1 tới mới nhất
            'max_quantity' => $log->max_quantity,
            'logged_at'    => $log->logged_at->format('d/m/Y H:i:s'),
            'is_latest'    => $index === 0,
        ]);
    }
}
