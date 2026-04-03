<?php

namespace App\Filament\Widgets;

use App\Models\IngredientImportLog;
use App\Models\OrderProfitLog;
use App\Models\Product;
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
use Illuminate\Support\Facades\DB;

class ProductBatchTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Danh sách sản phẩm';
    protected int|string|array $columnSpan = 'full';
    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        return $table
            ->query(Product::query()->with(['primaryImage', 'recipeDetails.ingredient']))
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

                TextColumn::make('import_count')
                    ->label('Số lần nhập')
                    ->state(function (Product $record): int {
                        $ids = $record->recipeDetails->pluck('ingredient_id');
                        return $ids->isNotEmpty()
                            ? IngredientImportLog::whereIn('ingredient_id', $ids)
                                ->distinct('import_order_id')
                                ->count('import_order_id')
                            : 0;
                    })
                    ->badge()
                    ->color('info')
                    ->alignEnd(),

                TextColumn::make('cost_price')
                    ->label('Giá vốn hiện tại')
                    ->state(fn(Product $record): float => $record->cost_price)
                    ->formatStateUsing(fn(float $state): string => number_format($state, 0, ',', '.') . 'đ')
                    ->alignEnd(),

                TextColumn::make('avg_profit_margin')
                    ->label('% LN trung bình')
                    ->state(fn(Product $record): float => round(
                        (float)(OrderProfitLog::where('product_id', $record->id)->avg('profit_margin') ?? 0), 1
                    ))
                    ->formatStateUsing(fn(float $state): string => $state . '%')
                    ->badge()
                    ->color(fn(float $state): string => match (true) {
                        $state >= 40 => 'success',
                        $state >= 20 => 'warning',
                        default => 'danger',
                    })
                    ->alignEnd(),

                TextColumn::make('recommended_price')
                    ->label('Giá bán hiện tại')
                    ->formatStateUsing(fn($state): string => number_format((float)$state, 0, ',', '.') . 'đ')
                    ->alignEnd(),
            ])
            ->actions([
                Action::make('view_batches')
                    ->label('Chi tiết')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->slideOver()
                    ->infolist(function (Product $record) {
                        $batches = $this->getBatches($record);

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
                                            TextEntry::make('recommended_price')
                                                ->label('Giá bán hiện tại')
                                                ->state(number_format($record->recommended_price, 0, ',', '.') . 'đ'),
                                            TextEntry::make('cost_price')
                                                ->label('Giá vốn hiện tại')
                                                ->state(number_format($record->cost_price, 0, ',', '.') . 'đ'),
                                        ])->columns(2),
                                ]),

                            Section::make('Lịch sử lô nhập')
                                ->schema(
                                    $batches->map(fn($item, $index) => Grid::make(6)
                                        ->schema([
                                            TextEntry::make("lot_{$index}")
                                                ->label('Lần nhập')
                                                ->state($item['lot'])
                                                ->badge()
                                                ->color($item['is_latest'] ? 'success' : 'gray'),
                                            TextEntry::make("code_{$index}")
                                                ->label('Mã phiếu')
                                                ->state($item['code']),
                                            TextEntry::make("date_{$index}")
                                                ->label('Ngày nhập')
                                                ->state($item['date']),
                                            TextEntry::make("cost_{$index}")
                                                ->label('Giá vốn')
                                                ->state(number_format($item['cost_price'], 0, ',', '.') . 'đ'),
                                            TextEntry::make("profit_{$index}")
                                                ->label('% Lợi nhuận')
                                                ->state($item['profit_pct'] . '%')
                                                ->badge()
                                                ->color(match (true) {
                                                    $item['profit_pct'] >= 40 => 'success',
                                                    $item['profit_pct'] >= 20 => 'warning',
                                                    $item['profit_pct'] > 0 => 'info',
                                                    default => 'danger',
                                                }),
                                            TextEntry::make("sell_{$index}")
                                                ->label('Giá bán TB')
                                                ->state($item['sell_price'] > 0 ? number_format($item['sell_price'], 0, ',', '.') . 'đ' : 'Chưa có đơn'),
                                        ])
                                    )->toArray()
                                )
                                ->columns(1)
                                ->visible($batches->isNotEmpty()),

                            Section::make('')
                                ->schema([
                                    TextEntry::make('empty')
                                        ->hiddenLabel()
                                        ->state('Chưa có lịch sử nhập kho nào.')
                                ])
                                ->visible($batches->isEmpty()),
                        ];
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng')
                    ->modalWidth('7xl'),
            ])
            ->defaultSort('name')
            ->paginated([10, 25, 50])
            ->striped();
    }

    private function getBatches(Product $record): Collection
    {
        $ingredientIds = $record->recipeDetails->pluck('ingredient_id');

        if ($ingredientIds->isEmpty()) {
            return collect();
        }

        $importOrders = IngredientImportLog::whereIn('ingredient_id', $ingredientIds)
            ->select(
                'import_order_id',
                'import_order_code',
                DB::raw('MIN(imported_at) as imported_at'),
            )
            ->groupBy('import_order_id', 'import_order_code')
            ->orderBy('imported_at')
            ->get()
            ->values();

        if ($importOrders->isEmpty()) {
            return collect();
        }

        $allLogs = IngredientImportLog::whereIn('ingredient_id', $ingredientIds)
            ->orderBy('imported_at')
            ->get()
            ->groupBy('ingredient_id');

        $batches = [];

        foreach ($importOrders as $index => $importOrder) {
            $importDate     = $importOrder->imported_at;
            $nextImportDate = $importOrders[$index + 1]->imported_at ?? null;

            $costAtTime = 0.0;
            foreach ($record->recipeDetails as $detail) {
                $logs = $allLogs->get($detail->ingredient_id, collect());

                $costAfter = $logs
                    ->filter(fn ($l) => $l->imported_at <= $importDate)
                    ->sortByDesc('imported_at')
                    ->first()
                    ?->cost_price_after
                    ?? $detail->ingredient?->cost_price
                    ?? 0;

                $costAtTime += (float) $detail->amount * (float) $costAfter;
            }
            $costAtTime = round($costAtTime, 2);

            $sellQuery = OrderProfitLog::where('product_id', $record->id)
                ->where('logged_at', '>=', $importDate);
            if ($nextImportDate) {
                $sellQuery->where('logged_at', '<', $nextImportDate);
            }
            $avgSellPrice = round((float) ($sellQuery->avg('unit_price') ?? 0), 2);

            $profitPct = ($costAtTime > 0 && $avgSellPrice > 0)
                ? round(($avgSellPrice - $costAtTime) / $costAtTime * 100, 1)
                : 0.0;

            $batches[] = [
                'lot'        => $index + 1,
                'code'       => $importOrder->import_order_code,
                'date'       => $importDate->format('d/m/Y'),
                'cost_price' => $costAtTime,
                'sell_price' => $avgSellPrice,
                'profit_pct' => $profitPct,
                'is_latest'  => false,
            ];
        }

        $batches              = array_reverse($batches);
        $batches[0]['is_latest'] = true;

        return collect($batches);
    }
}
