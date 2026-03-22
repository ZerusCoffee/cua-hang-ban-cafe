<?php

namespace App\Filament\Widgets;

use App\Models\Ingredient;
use App\Models\IngredientImportLog;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\DB;
use Filament\Infolists\Components\TextEntry;

class ImportStatsWidget extends TableWidget
{
    protected static ?string $heading = 'Thống kê nhập nguyên liệu';

    protected int | string | array $columnSpan = 'full';

    public ?string $fromDate = null;
    public ?string $toDate = null;
    public ?int $selectedIngredientId = null;

    public function mount(): void
    {
        $this->fromDate = now()->startOfMonth()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
        $this->selectedIngredientId = null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $sub = IngredientImportLog::query()
                    ->when($this->fromDate, fn($q) => $q->whereDate('imported_at', '>=', $this->fromDate))
                    ->when($this->toDate, fn($q) => $q->whereDate('imported_at', '<=', $this->toDate))
                    ->when($this->selectedIngredientId, fn($q) => $q->where('ingredient_id', $this->selectedIngredientId))
                    ->select(
                        'ingredient_id',
                        DB::raw('MIN(id) as id'),
                        DB::raw('SUM(quantity) as total_quantity'),
                        DB::raw('SUM(quantity * unit_price) as total_value'),
                        DB::raw('AVG(unit_price) as avg_price'),
                        DB::raw('COUNT(*) as import_count')
                    )
                    ->groupBy('ingredient_id');

                // Alias = tên bảng gốc → Filament append ingredient_import_logs.id
                // → resolve vào MIN(id) trong subquery → không lỗi ONLY_FULL_GROUP_BY
                return IngredientImportLog::fromSub($sub, 'ingredient_import_logs')
                    ->with(['ingredient.unit']);
            })
            ->columns([
                TextColumn::make('ingredient.sku')
                    ->label('Mã NL')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('ingredient.name')
                    ->label('Tên nguyên liệu')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('ingredient.unit.symbol')
                    ->label('ĐVT')
                    ->badge()
                    ->color('info'),

                TextColumn::make('import_count')
                    ->label('Số lần nhập')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('total_quantity')
                    ->label('Tổng số lượng')
                    ->formatStateUsing(fn($state, $record) =>
                        number_format($state, 2) . ' ' . ($record->ingredient?->unit?->symbol ?? ''))
                    ->sortable()
                    ->weight('bold')
                    ->alignRight(),

                TextColumn::make('avg_price')
                    ->label('Giá TB')
                    ->money('VND')
                    ->sortable()
                    ->alignRight(),

                TextColumn::make('total_value')
                    ->label('Tổng giá trị')
                    ->money('VND')
                    ->sortable()
                    ->weight('bold')
                    ->alignRight()
                    ->color('success'),
            ])
            ->filters([
                SelectFilter::make('ingredient_id')
                    ->label('Nguyên liệu')
                    ->relationship('ingredient', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(false),
            ])
            ->headerActions([
                Action::make('filter')
                    ->label('Lọc')
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
                        Select::make('ingredient_id')
                            ->label('Nguyên liệu')
                            ->options(Ingredient::pluck('name', 'id'))
                            ->placeholder('Tất cả')
                            ->searchable(),
                    ])
                    ->action(function (array $data) {
                        $this->fromDate = $data['from_date'];
                        $this->toDate = $data['to_date'];
                        $this->selectedIngredientId = $data['ingredient_id'] ?? null;
                    }),

                Action::make('summary')
                    ->label('Tổng quan')
                    ->icon('heroicon-o-chart-bar')
                    ->color('warning')
                    ->modalHeading('Thống kê tổng quan')
                    ->infolist(function () {
                        $totalImports = IngredientImportLog::query()
                            ->when($this->fromDate, fn($q) => $q->whereDate('imported_at', '>=', $this->fromDate))
                            ->when($this->toDate, fn($q) => $q->whereDate('imported_at', '<=', $this->toDate))
                            ->count();

                        $totalQuantity = IngredientImportLog::query()
                            ->when($this->fromDate, fn($q) => $q->whereDate('imported_at', '>=', $this->fromDate))
                            ->when($this->toDate, fn($q) => $q->whereDate('imported_at', '<=', $this->toDate))
                            ->sum('quantity');

                        $totalValue = IngredientImportLog::query()
                            ->when($this->fromDate, fn($q) => $q->whereDate('imported_at', '>=', $this->fromDate))
                            ->when($this->toDate, fn($q) => $q->whereDate('imported_at', '<=', $this->toDate))
                            ->sum(DB::raw('quantity * unit_price'));

                        $ingredientCount = IngredientImportLog::query()
                            ->when($this->fromDate, fn($q) => $q->whereDate('imported_at', '>=', $this->fromDate))
                            ->when($this->toDate, fn($q) => $q->whereDate('imported_at', '<=', $this->toDate))
                            ->distinct('ingredient_id')
                            ->count('ingredient_id');

                        return [
                            Grid::make(2)
                                ->schema([
                                    Section::make('Thời gian')
                                        ->schema([
                                            TextEntry::make('time_range')
                                                ->hiddenLabel()
                                                ->state(\Carbon\Carbon::parse($this->fromDate)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($this->toDate)->format('d/m/Y')),
                                        ]),
                                    Section::make('Số nguyên liệu')
                                        ->schema([
                                            TextEntry::make('ingredient_count')
                                                ->hiddenLabel()
                                                ->state(number_format($ingredientCount)),
                                        ]),
                                ]),

                            Grid::make(2)
                                ->schema([
                                    Section::make('Tổng số lần nhập')
                                        ->schema([
                                            TextEntry::make('total_imports')
                                                ->hiddenLabel()
                                                ->state(number_format($totalImports))
                                                ->color('primary')
                                                ->size('text-2xl'),
                                        ]),
                                    Section::make('Tổng số lượng')
                                        ->schema([
                                            TextEntry::make('total_quantity')
                                                ->hiddenLabel()
                                                ->state(number_format($totalQuantity, 2))
                                                ->color('success')
                                                ->size('text-2xl'),
                                        ]),
                                ]),

                            Section::make('Tổng giá trị nhập')
                                ->schema([
                                    TextEntry::make('total_value')
                                        ->hiddenLabel()
                                        ->state(number_format($totalValue, 0) . ' VNĐ')
                                        ->color('warning')
                                        ->size('text-3xl')
                                        ->weight('bold'),
                                ]),
                        ];
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng'),
            ])
            ->emptyStateHeading('Không có dữ liệu nhập kho')
            ->emptyStateDescription('Thử thay đổi khoảng thời gian hoặc chọn nguyên liệu khác')
            ->emptyStateIcon('heroicon-o-document-text')
            ->defaultSort('total_value', 'desc')
            ->striped();
    }
}
