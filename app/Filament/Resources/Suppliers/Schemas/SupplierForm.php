<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
       return $schema
            ->components([
                TextInput::make('name')
                    ->label('Tên nhà cung cấp')
                    ->required()
                    ->maxLength(255),

                TextInput::make('phone')
                    ->label('Số điện thoại')
                    ->tel()
                    ->nullable(),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->nullable(),

                Textarea::make('address')
                    ->label('Địa chỉ')
                    ->rows(2)
                    ->nullable(),

                Textarea::make('notes')
                    ->label('Ghi chú')
                    ->rows(2)
                    ->nullable(),
            ])
            ->columns(2);
    }
}
