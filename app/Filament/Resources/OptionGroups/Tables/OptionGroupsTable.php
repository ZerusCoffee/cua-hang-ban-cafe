<?php

namespace App\Filament\Resources\OptionGroups\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class OptionGroupsTable
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
                    ->label('Tên nhóm')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('options_count')
                    ->label('Số giá trị')
                    ->counts('options')
                    ->badge()
                    ->color('success'),

                IconColumn::make('is_required')
                    ->label('Bắt buộc')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('type')
                    ->label('Kiểu')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state === 'single' ? 'Chọn 1' : 'Chọn nhiều')
                    ->color(fn($state) => $state === 'single' ? 'info' : 'warning'),

                TextColumn::make('min')
                    ->label('Min')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('max')
                    ->label('Max')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'single' => 'Chọn 1',
                        'multiple' => 'Chọn nhiều',
                    ]),
                TernaryFilter::make('is_required')
                    ->label('Bắt buộc'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
