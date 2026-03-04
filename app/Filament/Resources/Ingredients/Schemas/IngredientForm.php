<?php

namespace App\Filament\Resources\Ingredients\Schemas;

use App\Models\Unit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IngredientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Tên nguyên liệu')
                    ->required()
                    ->maxLength(255),

                Select::make('unit_id')
                    ->label('Đơn vị')
                    ->options(Unit::query()->pluck('symbol', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Tên đơn vị')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('symbol')
                            ->label('Ký hiệu')
                            ->required()
                            ->maxLength(50),
                    ])
                    ->createOptionUsing(fn(array $data): int => Unit::create($data)->id),

                TextInput::make('sku')
                    ->label('SKU')
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->placeholder('Tự động nếu để trống'),

                TextInput::make('price')
                    ->label('Giá Nhập')
                    ->required()
                    ->numeric()
                    ->suffix('₫')
                    ->minValue(0),

                TextInput::make('stock')
                    ->label('Tồn kho')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->minValue(0),

                TextInput::make('threshold')
                    ->label('Ngưỡng cảnh báo')
                    ->required()
                    ->numeric()
                    ->default(50)
                    ->minValue(0),
            ]);
    }
}
