<?php

namespace App\Filament\Resources\OptionGroups\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OptionGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Thông tin nhóm')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Tên nhóm')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('VD: Size, Đường, Đá...'),

                        Select::make('type')
                            ->label('Kiểu chọn')
                            ->options([
                                'single' => 'Chọn 1',
                                'multiple' => 'Chọn nhiều',
                            ])
                            ->required()
                            ->default('single'),

                        Toggle::make('is_required')
                            ->label('Bắt buộc chọn')
                            ->default(false),

                        TextInput::make('min')
                            ->label('Chọn tối thiểu')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('0 = không bắt buộc'),

                        TextInput::make('max')
                            ->label('Chọn tối đa')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText('Số lượng tối đa được chọn'),
                    ]),

                Section::make('Các giá trị tùy chọn')
                    ->schema([
                        Repeater::make('options')
                            ->relationship('options')
                            ->schema([
                                TextInput::make('value')
                                    ->label('Giá trị')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('VD: M, L, XL / 0%, 30%, 50%...'),
                            ])
                            ->columns(1)
                            ->addActionLabel('+ Thêm giá trị')
                            ->reorderable()
                            ->defaultItems(1)
                            ->columnSpanFull()
                    ]),
            ]);
    }
}
