<?php

namespace App\Filament\Resources\ImportOrders\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ImportOrdersTable
{
     public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Mã phiếu')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('supplier.name')
                    ->label('Nhà cung cấp')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'draft'     => 'warning',
                        'completed' => 'success',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'draft'     => 'Đang đợi xác nhận',
                        'completed' => 'Hoàn thành',
                    }),

                TextColumn::make('details_count')
                    ->label('Số Nguyên Liệu')
                    ->counts('details')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Tổng tiền')
                    ->getStateUsing(fn ($record) => $record->details->sum(fn ($d) => $d->quantity * $d->unit_price))
                    ->numeric()
                    ->suffix('₫')
                    ->sortable(),

                TextColumn::make('imported_at')
                    ->label('Ngày nhập')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'draft'     => 'Nháp',
                        'completed' => 'Hoàn thành',
                    ]),

                SelectFilter::make('supplier_id')
                    ->label('Nhà cung cấp')
                    ->relationship('supplier', 'name'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn ($record) => $record->status === 'completed'),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
