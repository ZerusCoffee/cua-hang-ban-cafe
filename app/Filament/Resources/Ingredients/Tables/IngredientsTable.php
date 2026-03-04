<?php

namespace App\Filament\Resources\Ingredients\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

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
