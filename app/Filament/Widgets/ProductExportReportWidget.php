<?php

namespace App\Filament\Widgets;

use App\Models\OrderProfitLog;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Filament\Infolists\Components\TextEntry;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ProductExportReportWidget extends TableWidget
{
    protected static ?string $heading = 'Báo cáo xuất sản phẩm';

    protected int|string|array $columnSpan = 'full';

    public ?string $fromDate = null;
    public ?string $toDate = null;
    public ?int $selectedProductId = null;
    public static bool $isLazy = false;

    public function mount(): void
    {
        $this->fromDate = now()->startOfMonth()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
        $this->selectedProductId = null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $sub = OrderProfitLog::query()
                    ->when($this->fromDate, fn($q) => $q->whereDate('logged_at', '>=', $this->fromDate))
                    ->when($this->toDate, fn($q) => $q->whereDate('logged_at', '<=', $this->toDate))
                    ->when($this->selectedProductId, fn($q) => $q->where('product_id', $this->selectedProductId))
                    ->select(
                        'product_id',
                        'product_name',
                        'product_sku',
                        DB::raw('MIN(id) as id'),
                        DB::raw('SUM(quantity) as total_quantity'),
                        DB::raw('SUM(total_price) as total_revenue'),
                        DB::raw('SUM(total_cost) as total_cost'),
                        DB::raw('SUM(total_profit) as total_profit'),
                        DB::raw('AVG(profit_margin) as avg_margin'),
                        DB::raw('COUNT(DISTINCT order_id) as order_count')
                    )
                    ->groupBy('product_id', 'product_name', 'product_sku');

                return OrderProfitLog::fromSub($sub, 'order_profit_logs')
                    ->with('product');
            })
            ->columns([
                TextColumn::make('product_sku')
                    ->label('Mã SP')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('product_name')
                    ->label('Tên sản phẩm')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('order_count')
                    ->label('Số đơn')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->color('info'),

                TextColumn::make('total_quantity')
                    ->label('Tổng SL xuất')
                    ->numeric()
                    ->sortable()
                    ->weight('bold')
                    ->alignRight()
                    ->formatStateUsing(fn($state) => number_format($state, 0))
                    ->color('primary'),

                TextColumn::make('total_revenue')
                    ->label('Doanh thu')
                    ->money('VND')
                    ->sortable()
                    ->alignRight()
                    ->color('success'),

                TextColumn::make('total_cost')
                    ->label('Chi phí')
                    ->money('VND')
                    ->sortable()
                    ->alignRight()
                    ->color('danger')
                    ->toggleable(),

                TextColumn::make('total_profit')
                    ->label('Lợi nhuận')
                    ->money('VND')
                    ->sortable()
                    ->alignRight()
                    ->weight('bold')
                    ->color(fn($state) => $state >= 0 ? 'success' : 'danger'),

                TextColumn::make('avg_margin')
                    ->label('Tỉ lệ LN TB')
                    ->formatStateUsing(fn($state) => number_format($state, 1) . '%')
                    ->sortable()
                    ->alignRight()
                    ->color(fn($state) => match (true) {
                        $state >= 30 => 'success',
                        $state >= 15 => 'warning',
                        default => 'danger',
                    })
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Sản phẩm')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(false),
            ])
            ->headerActions([
                Action::make('filter')
                    ->label('Lọc thời gian')
                    ->icon('heroicon-o-funnel')
                    ->color('primary')
                    ->form([
                        DatePicker::make('from_date')
                            ->label('Từ ngày')
                            ->required()
                            ->default($this->fromDate ?? now()->startOfMonth()),
                        DatePicker::make('to_date')
                            ->label('Đến ngày')
                            ->required()
                            ->default($this->toDate ?? now())
                            ->afterOrEqual('from_date'),
                        Select::make('product_id')
                            ->label('Sản phẩm')
                            ->options(Product::pluck('name', 'id'))
                            ->placeholder('Tất cả sản phẩm')
                            ->searchable(),
                    ])
                    ->action(function (array $data) {
                        $this->fromDate = $data['from_date'];
                        $this->toDate = $data['to_date'];
                        $this->selectedProductId = $data['product_id'] ?? null;

                        Notification::make()
                            ->title('Đã cập nhật bộ lọc')
                            ->success()
                            ->send();
                    }),

                Action::make('summary')
                    ->label('Tổng quan')
                    ->icon('heroicon-o-chart-bar')
                    ->color('warning')
                    ->modalHeading('Thống kê tổng quan xuất hàng')
                    ->infolist(function () {
                        $totalOrders = OrderProfitLog::query()
                            ->when($this->fromDate, fn($q) => $q->whereDate('logged_at', '>=', $this->fromDate))
                            ->when($this->toDate, fn($q) => $q->whereDate('logged_at', '<=', $this->toDate))
                            ->when($this->selectedProductId, fn($q) => $q->where('product_id', $this->selectedProductId))
                            ->distinct('order_id')
                            ->count('order_id');

                        $totalQuantity = OrderProfitLog::query()
                            ->when($this->fromDate, fn($q) => $q->whereDate('logged_at', '>=', $this->fromDate))
                            ->when($this->toDate, fn($q) => $q->whereDate('logged_at', '<=', $this->toDate))
                            ->when($this->selectedProductId, fn($q) => $q->where('product_id', $this->selectedProductId))
                            ->sum('quantity');

                        $totalRevenue = OrderProfitLog::query()
                            ->when($this->fromDate, fn($q) => $q->whereDate('logged_at', '>=', $this->fromDate))
                            ->when($this->toDate, fn($q) => $q->whereDate('logged_at', '<=', $this->toDate))
                            ->when($this->selectedProductId, fn($q) => $q->where('product_id', $this->selectedProductId))
                            ->sum('total_price');

                        $totalCost = OrderProfitLog::query()
                            ->when($this->fromDate, fn($q) => $q->whereDate('logged_at', '>=', $this->fromDate))
                            ->when($this->toDate, fn($q) => $q->whereDate('logged_at', '<=', $this->toDate))
                            ->when($this->selectedProductId, fn($q) => $q->where('product_id', $this->selectedProductId))
                            ->sum('total_cost');

                        $totalProfit = OrderProfitLog::query()
                            ->when($this->fromDate, fn($q) => $q->whereDate('logged_at', '>=', $this->fromDate))
                            ->when($this->toDate, fn($q) => $q->whereDate('logged_at', '<=', $this->toDate))
                            ->when($this->selectedProductId, fn($q) => $q->where('product_id', $this->selectedProductId))
                            ->sum('total_profit');

                        $avgMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

                        $productCount = OrderProfitLog::query()
                            ->when($this->fromDate, fn($q) => $q->whereDate('logged_at', '>=', $this->fromDate))
                            ->when($this->toDate, fn($q) => $q->whereDate('logged_at', '<=', $this->toDate))
                            ->when($this->selectedProductId, fn($q) => $q->where('product_id', $this->selectedProductId))
                            ->distinct('product_id')
                            ->count('product_id');

                        $topProducts = OrderProfitLog::query()
                            ->with('product')
                            ->when($this->fromDate, fn($q) => $q->whereDate('logged_at', '>=', $this->fromDate))
                            ->when($this->toDate, fn($q) => $q->whereDate('logged_at', '<=', $this->toDate))
                            ->when($this->selectedProductId, fn($q) => $q->where('product_id', $this->selectedProductId))
                            ->select('product_id', 'product_name', DB::raw('SUM(quantity) as total_qty'))
                            ->groupBy('product_id', 'product_name')
                            ->orderByDesc('total_qty')
                            ->limit(5)
                            ->get();

                        return [
                            Grid::make(2)
                                ->schema([
                                    Section::make('Thời gian')
                                        ->schema([
                                            TextEntry::make('time_range')
                                                ->hiddenLabel()
                                                ->state(\Carbon\Carbon::parse($this->fromDate)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($this->toDate)->format('d/m/Y')),
                                        ]),
                                    Section::make('Số sản phẩm')
                                        ->schema([
                                            TextEntry::make('product_count')
                                                ->hiddenLabel()
                                                ->state(number_format($productCount)),
                                        ]),
                                ]),

                            Grid::make(3)
                                ->schema([
                                    Section::make('Tổng đơn hàng')
                                        ->schema([
                                            TextEntry::make('total_orders')
                                                ->hiddenLabel()
                                                ->state(number_format($totalOrders))
                                                ->color('info')
                                                ->size('text-2xl'),
                                        ]),
                                    Section::make('Tổng số lượng')
                                        ->schema([
                                            TextEntry::make('total_quantity')
                                                ->hiddenLabel()
                                                ->state(number_format($totalQuantity))
                                                ->color('primary')
                                                ->size('text-2xl'),
                                        ]),
                                    Section::make('Tỉ lệ LN TB')
                                        ->schema([
                                            TextEntry::make('avg_margin')
                                                ->hiddenLabel()
                                                ->state(number_format($avgMargin, 1) . '%')
                                                ->color($avgMargin >= 20 ? 'success' : 'warning')
                                                ->size('text-2xl'),
                                        ]),
                                ]),

                            Grid::make(3)
                                ->schema([
                                    Section::make('Tổng doanh thu')
                                        ->schema([
                                            TextEntry::make('total_revenue')
                                                ->hiddenLabel()
                                                ->state(number_format($totalRevenue, 0) . ' VNĐ')
                                                ->color('success')
                                                ->size('text-2xl'),
                                        ]),
                                    Section::make('Tổng chi phí')
                                        ->schema([
                                            TextEntry::make('total_cost')
                                                ->hiddenLabel()
                                                ->state(number_format($totalCost, 0) . ' VNĐ')
                                                ->color('danger')
                                                ->size('text-2xl'),
                                        ]),
                                    Section::make('Tổng lợi nhuận')
                                        ->schema([
                                            TextEntry::make('total_profit')
                                                ->hiddenLabel()
                                                ->state(number_format($totalProfit, 0) . ' VNĐ')
                                                ->color($totalProfit >= 0 ? 'success' : 'danger')
                                                ->size('text-2xl')
                                                ->weight('bold'),
                                        ]),
                                ]),

                            Section::make('Top 5 sản phẩm bán chạy nhất')
                                ->schema(
                                    $topProducts->map(fn($item, $index) => TextEntry::make("top_{$index}")
                                        ->label($item->product_name)
                                        ->state(number_format($item->total_qty) . ' sản phẩm')
                                    )->toArray()
                                )
                                ->columns(2)
                                ->visible($topProducts->count() > 0),
                        ];
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng')
                    ->modalWidth('7xl'),

                ExportAction::make()
                    ->label('Xuất Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename(date('Y-m-d') . '-san-pham')
                    ]),

                Action::make('best_sellers')
                    ->label('Top bán chạy')
                    ->icon('heroicon-o-fire')
                    ->color('danger')
                    ->action(function () {
                        $this->dispatch('showBestSellers');
                    }),
            ])
            ->recordActions([
                Action::make('details')
                    ->label('Chi tiết')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn($record) => 'Chi tiết xuất: ' . $record->product_name)
                    ->infolist(function ($record) {
                        $details = OrderProfitLog::query()
                            ->where('product_id', $record->product_id)
                            ->when($this->fromDate, fn($q) => $q->whereDate('logged_at', '>=', $this->fromDate))
                            ->when($this->toDate, fn($q) => $q->whereDate('logged_at', '<=', $this->toDate))
                            ->select(
                                DB::raw('DATE(logged_at) as date'),
                                DB::raw('SUM(quantity) as daily_qty'),
                                DB::raw('SUM(total_price) as daily_revenue'),
                                DB::raw('SUM(total_profit) as daily_profit')
                            )
                            ->groupBy('date')
                            ->orderBy('date', 'desc')
                            ->limit(10)
                            ->get();

                        return [
                            Section::make('Thống kê nhanh')
                                ->schema([
                                    TextEntry::make('product_name')->label('Tên SP')->state($record->product_name),
                                    TextEntry::make('total_qty')->label('Tổng SL')->state(number_format($record->total_quantity)),
                                    TextEntry::make('total_revenue')->label('Doanh thu')->state(number_format($record->total_revenue, 0) . ' VNĐ'),
                                    TextEntry::make('total_profit')->label('Lợi nhuận')->state(number_format($record->total_profit, 0) . ' VNĐ'),
                                ])->columns(2),

                            Section::make('Chi tiết theo ngày')
                                ->schema(
                                    $details->map(fn($item, $index) => TextEntry::make("detail_{$index}")
                                        ->label(\Carbon\Carbon::parse($item->date)->format('d/m/Y'))
                                        ->state("SL: {$item->daily_qty} | DT: " . number_format($item->daily_revenue, 0) . "đ | LN: " . number_format($item->daily_profit, 0) . "đ")
                                    )->toArray()
                                )
                                ->columns(2),
                        ];
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng'),
            ])
            ->emptyStateHeading('Không có dữ liệu xuất hàng')
            ->emptyStateDescription('Thử thay đổi khoảng thời gian hoặc chọn sản phẩm khác')
            ->emptyStateIcon('heroicon-o-document-text')
            ->defaultSort('total_quantity', 'desc')
            ->striped();
    }
}
