<?php

namespace App\Filament\Resources\Ingredients\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IngredientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width('60px'),

                TextColumn::make('name')
                    ->label('Tên nguyên liệu')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unit.symbol')
                    ->label('Đơn vị')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('cost_price')
                    ->label('Giá')
                    ->numeric()
                    ->suffix('₫')
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Tồn kho')
                    ->numeric()
                    ->sortable()
                    ->color(fn($state) => $state <= 0 ? 'danger' : 'success'),

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

                TextColumn::make('threshold')
                    ->label('Ngưỡng cảnh báo')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'out' => 'Đã hết',
                        'low' => 'Sắp hết',
                        'ok' => 'Còn hàng',
                    ])
                    ->query(fn(Builder $query, array $data) => match ($data['value'] ?? null) {
                        'out' => $query->where('stock', '<=', 0),
                        'low' => $query->whereColumn('stock', '<=', 'threshold')->where('stock', '>', 0),
                        'ok' => $query->whereColumn('stock', '>', 'threshold'),
                        default => $query,
                    }),

                Filter::make('low_stock')
                    ->label('Tồn kho thấp hơn')
                    ->form([
                        TextInput::make('threshold')
                            ->label('Tồn kho thấp hơn')
                            ->numeric()
                            ->placeholder('VD: 10'),
                    ])
                    ->query(fn(Builder $query, array $data) => filled($data['threshold'])
                        ? $query->where('stock', '<', $data['threshold'])
                        : $query
                    )
                    ->indicateUsing(fn(array $data) => filled($data['threshold']) ? 'Tồn kho < ' . $data['threshold'] : null
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->action(fn($record) => $record->safeDelete())
                    ->requiresConfirmation(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }
}
