<?php

namespace App\Filament\Widgets;

use App\Models\Ingredient;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Filament\Tables\Filters\SelectFilter;

class StockLookupWidget extends TableWidget
{
    protected static ?string $heading = 'Tra cứu tồn kho theo thời điểm';

    protected int|string|array $columnSpan = 'full';

    public ?string $lookupDate = null;

    public function mount(): void
    {
        $this->lookupDate = now()->format('Y-m-d');
    }

    protected function getListeners(): array
    {
        return [
            'updateDate' => 'setLookupDate',
        ];
    }

    public function setLookupDate($date)
    {
        $this->lookupDate = $date;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Ingredient::query()->with('unit'))
            ->columns([
                TextColumn::make('sku')
                    ->label('Mã NL')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('name')
                    ->label('Tên nguyên liệu')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Tồn hiện tại')
                    ->formatStateUsing(fn($state, $record) => number_format($state, 2) . ' ' . ($record->unit?->symbol ?? '')),

                TextColumn::make('stock_lookup')
                    ->label(fn() => 'Tồn ngày ' . ($this->lookupDate ? \Carbon\Carbon::parse($this->lookupDate)->format('d/m/Y') : ''))
                    ->getStateUsing(function ($record) {
                        if (!$this->lookupDate) return 'Chọn ngày';

                        $targetDate = \Carbon\Carbon::parse($this->lookupDate)->endOfDay();

                        $logs = $record->importLogs()
                            ->where('imported_at', '<=', $targetDate)
                            ->get();

                        if ($logs->isEmpty()) {
                            return $record->created_at <= $targetDate ? '0' : 'Chưa có';
                        }

                        $totalImported = $logs->sum('quantity');

                        return number_format($totalImported, 2) . ' ' . ($record->unit?->symbol ?? '');
                    })
                    ->color(function ($record, $state) {
                        if ($state === 'Chọn ngày' || $state === 'Chưa có') return 'gray';

                        $value = (float)str_replace([',', ' '], '', $state);

                        return match (true) {
                            $value <= 0 => 'danger',
                            $value <= $record->threshold => 'warning',
                            default => 'success',
                        };
                    }),

                TextColumn::make('threshold')
                    ->label('Ngưỡng')
                    ->formatStateUsing(fn($state, $record) => number_format($state, 2) . ' ' . ($record->unit?->symbol ?? '')),

                TextColumn::make('unit.name')
                    ->label('Đơn vị')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                SelectFilter::make('unit_id')
                    ->label('Đơn vị')
                    ->relationship('unit', 'name'),
            ])
            ->headerActions([
                Action::make('date_picker')
                    ->label('')
                    ->extraAttributes(['class' => 'p-0'])
                    ->view('filament.widgets.date-picker'),
            ])
            ->defaultSort('name');
    }
}
