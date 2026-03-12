<?php

namespace App\Filament\Pages;

use App\Models\Ingredient;
use App\Models\IngredientImportLog;
use App\Models\IngredientStockLog;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WarehousePage extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $navigationLabel = 'Quản lý kho';
    protected static string|null|\UnitEnum $navigationGroup = 'Nhập hàng';
    protected static ?string $title = 'Quản lý kho';
    protected static ?int $navigationSort = 21;
    protected string $view = 'filament.pages.warehouse-page';

    // Tab đang active
    public string $activeTab = 'stock';

    // Form tra cứu tồn
    public ?array $stockAtData = [];
    public ?array $stockAtResult = null;

    // Form báo cáo
    public ?array $reportData = [];
    public ?array $reportResult = null;

    public function mount(): void
    {
        $this->stockAtForm->fill(['at' => now()->format('Y-m-d H:i:s')]);
        $this->reportForm->fill([
            'from' => now()->startOfMonth()->format('Y-m-d H:i:s'),
            'until' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    protected function getForms(): array
    {
        return ['stockAtForm', 'reportForm'];
    }

    public function stockAtForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('ingredient_id')
                    ->label('Nguyên liệu')
                    ->options(Ingredient::query()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),

                DateTimePicker::make('at')
                    ->label('Thời điểm tra cứu')
                    ->required(),
            ])
            ->columns(2)
            ->statePath('stockAtData');
    }

    public function reportForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('ingredient_id')
                    ->label('Nguyên liệu')
                    ->options(Ingredient::query()->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('Tất cả'),
                DateTimePicker::make('from')->label('Từ ngày')->required(),
                DateTimePicker::make('until')->label('Đến ngày')->required(),
            ])
            ->columns(3)
            ->statePath('reportData');
    }

    public function submitStockAt(): void
    {
        $data = $this->stockAtForm->getState();
        $ingredient = Ingredient::with('unit')->findOrFail($data['ingredient_id']);
        $at = Carbon::parse($data['at']);

        $log = IngredientImportLog::where('ingredient_id', $ingredient->id)
            ->where('imported_at', '<=', $at)
            ->orderBy('imported_at', 'desc')
            ->first();

        $this->stockAtResult = [
            'ingredient' => $ingredient->name,
            'unit' => $ingredient->unit?->symbol ?? '',
            'at' => $at->format('d/m/Y H:i'),
            'stock' => $log ? floatval($log->stock_after) : floatval($ingredient->stock),
            'cost_price' => $log ? floatval($log->cost_price_after) : floatval($ingredient->cost_price),
        ];
    }

    public function submitReport(): void
    {
        $data = $this->reportForm->getState();
        $from = Carbon::parse($data['from']);
        $until = Carbon::parse($data['until']);

        $imports = IngredientImportLog::query()
            ->whereBetween('imported_at', [$from, $until])
            ->when(!empty($data['ingredient_id']), fn($q) => $q->where('ingredient_id', $data['ingredient_id']))
            ->get();

        $exports = IngredientStockLog::query()
            ->where('type', 'export')
            ->whereBetween('logged_at', [$from, $until])
            ->when(!empty($data['ingredient_id']), fn($q) => $q->where('ingredient_id', $data['ingredient_id']))
            ->get();

        $rows = Ingredient::with('unit')
            ->when(!empty($data['ingredient_id']), fn($q) => $q->where('id', $data['ingredient_id']))
            ->get()
            ->map(fn($i) => [
                'name' => $i->name,
                'unit' => $i->unit?->symbol ?? '',
                'total_import' => floatval($imports->where('ingredient_id', $i->id)->sum('quantity')),
                'total_export' => abs(floatval($exports->where('ingredient_id', $i->id)->sum('quantity_change'))),
                'diff' => floatval($imports->where('ingredient_id', $i->id)->sum('quantity'))
                    - abs(floatval($exports->where('ingredient_id', $i->id)->sum('quantity_change'))),
                'stock_now' => floatval($i->stock),
            ])
            ->filter(fn($r) => $r['total_import'] > 0 || $r['total_export'] > 0)
            ->values();

        $this->reportResult = [
            'from' => $from->format('d/m/Y'),
            'until' => $until->format('d/m/Y'),
            'rows' => $rows->toArray(),
        ];
    }

    // Table tab 1: tồn kho
    public function table(Table $table): Table
    {
        return $table
            ->query(Ingredient::query()->with('unit'))
            ->columns([
                TextColumn::make('sku')->label('SKU')->searchable()->copyable(),
                TextColumn::make('name')->label('Nguyên liệu')->searchable()->sortable(),
                TextColumn::make('stock')
                    ->label('Tồn hiện tại')
                    ->formatStateUsing(fn($state, $record) => number_format($state, 2) . ' ' . ($record->unit?->symbol ?? '')
                    )
                    ->sortable()
                    ->color(fn($record) => match (true) {
                        $record->stock <= 0 => 'danger',
                        $record->stock <= $record->threshold => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('threshold')
                    ->label('Ngưỡng')
                    ->formatStateUsing(fn($state, $record) => number_format($state, 2) . ' ' . ($record->unit?->symbol ?? '')
                    ),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->state(fn($record) => match (true) {
                        $record->stock <= 0 => 'Đã hết',
                        $record->stock <= $record->threshold => 'Sắp hết',
                        default => 'Còn hàng',
                    })
                    ->color(fn(string $state) => match ($state) {
                        'Đã hết' => 'danger',
                        'Sắp hết' => 'warning',
                        'Còn hàng' => 'success',
                    }),
                TextColumn::make('cost_price')->label('Giá BQ')->money('VND')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('low_stock')
                    ->label('Trạng thái tồn kho')
                    ->trueLabel('Sắp hết / Đã hết')
                    ->falseLabel('Còn hàng')
                    ->queries(
                        true: fn(Builder $q) => $q->whereColumn('stock', '<=', 'threshold'),
                        false: fn(Builder $q) => $q->whereColumn('stock', '>', 'threshold'),
                    ),
            ])
            ->recordActions([
                Action::make('set_threshold')
                    ->label('Đặt ngưỡng')
                    ->icon('heroicon-o-bell-alert')
                    ->form([
                        TextInput::make('threshold')
                            ->label('Ngưỡng cảnh báo')
                            ->numeric()
                            ->required()
                            ->default(fn(Ingredient $record) => $record->threshold),
                    ])
                    ->action(fn(Ingredient $record, array $data) => $record->update(['threshold' => $data['threshold']])
                    ),
            ])
            ->defaultSort('stock', 'asc');
    }

    public function getImportLogs()
    {
        return IngredientImportLog::with('ingredient.unit')
            ->orderBy('imported_at', 'desc')
            ->paginate(20, pageName: 'logsPage');
    }
}
