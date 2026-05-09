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

                        TextInput::make('min')
                            ->label('Chọn tối thiểu')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('0 = không bắt buộc')
                            ->rules([
                                function ($get) {
                                    return function ($attribute, $value, $fail) use ($get) {
                                        if ($value > $get('max')) {
                                            $fail('Chọn tối thiểu không được lớn hơn chọn tối đa.');
                                        }
                                    };
                                }
                            ])
                            ->reactive(),

                        TextInput::make('max')
                            ->label('Chọn tối đa')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(100)
                            ->rules([
                                function ($get) {
                                    return function ($attribute, $value, $fail) use ($get) {
                                        if ($value < $get('min')) {
                                            $fail('Chọn tối đa không được nhỏ hơn chọn tối thiểu.');
                                        }
                                    };
                                }
                            ])
                            ->helperText('Số lượng tối đa được chọn')
                            ->reactive(),
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
                                Toggle::make('default')
                                    ->label('Mặc định')
                                    ->default(false)
                                    ->distinct()
                                    ->fixIndistinctState()
                            ])
                            ->columns(1)
                            ->addActionLabel('+ Thêm giá trị')
                            ->reorderable()
                            ->defaultItems(1)
                            ->rules([
                                fn($get) => function ($attribute, $value, $fail) use ($get) {
                                    $options = $get('options') ?? [];
                                    $defaultCount = collect($options)
                                        ->filter(fn($opt) => ($opt['default'] ?? false) === true)
                                        ->count();

                                    if ($defaultCount === 0) {
                                        $fail('Vui lòng chọn 1 giá trị mặc định.');
                                    }
                                }
                            ])
                            ->validationAttribute('các giá trị tùy chọn')
                            ->columnSpanFull()
                    ]),
            ]);
    }
}
