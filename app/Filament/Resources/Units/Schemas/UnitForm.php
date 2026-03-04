<?php

namespace App\Filament\Resources\Units\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Tên đơn vị')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Ví dụ: Kilogram'),

                TextInput::make('symbol')
                    ->label('Ký hiệu')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->placeholder('Ví dụ: kg'),
            ]);
    }
}
