<?php

namespace App\Filament\Resources\Customers\Relations;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';
    protected static ?string $title = 'Địa chỉ giao hàng';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')->label('Người nhận'),
                TextColumn::make('phone')->label('SĐT'),
                TextColumn::make('full_address')
                    ->label('Địa chỉ')
                    ->getStateUsing(fn($record) => $record->full_address),
                IconColumn::make('is_default')
                    ->label('Mặc định')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
            ])
            ->heading('Địa chỉ giao hàng');
    }
}
