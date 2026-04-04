<?php

namespace App\Filament\Resources\ImportOrders\Schemas;

use App\Models\Ingredient;
use App\Models\Supplier;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ImportOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Mã phiếu nhập')
                    ->placeholder('Tự động tạo (PN-0001)')
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit'),

                Select::make('supplier_id')
                    ->label('Nhà cung cấp')
                    ->options(Supplier::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn($record) => $record?->status === 'completed')
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Tên nhà cung cấp')
                            ->required(),
                        TextInput::make('phone')
                            ->label('Số điện thoại')
                            ->tel(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email(),
                    ])
                    ->createOptionUsing(fn(array $data): int => Supplier::create($data)->id),

                Textarea::make('notes')
                    ->label('Ghi chú')
                    ->rows(2)
                    ->nullable()
                    ->columnSpanFull(),

                DateTimePicker::make('imported_at')
                    ->label('Ngày nhập')
                    ->displayFormat('d/m/Y H:i')
                    ->default(now())
                    ->native(false)
                    ->disabled(fn($record) => $record?->status === 'completed'),
                Repeater::make('details')
                    ->label('Danh sách nguyên liệu')
                    ->relationship('details')
                    ->schema([
                        Select::make('ingredient_id')
                            ->label('Nguyên liệu')
                            ->options(
                                Ingredient::query()
                                    ->with('unit')
                                    ->get()
                                    ->mapWithKeys(fn($i) => [
                                        $i->id => $i->name . ' (' . ($i->unit?->symbol ?? '') . ')',
                                    ])
                            )
                            ->searchable()
                            ->required()
                            ->distinct()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->columnSpan(2),

                        TextInput::make('quantity')
                            ->label('Số lượng')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->live()
                            ->afterStateUpdated(fn($state, callable $get, callable $set) => $set('total_price', number_format($state * $get('unit_price'), 0, '.', ','))
                            ),

                        TextInput::make('unit_price')
                            ->label('Đơn giá')
                            ->required()
                            ->numeric()
                            ->suffix('₫')
                            ->minValue(1)
                            ->live()
                            ->afterStateUpdated(fn($state, callable $get, callable $set) => $set('total_price', number_format($state * $get('quantity'), 0, '.', ','))
                            ),

                        TextInput::make('total_price')
                            ->label('Thành tiền')
                            ->suffix('₫')
                            ->disabled()
                            ->dehydrated(false)
                    ])
                    ->columns(4)
                    ->addActionLabel('Thêm nguyên liệu')
                    ->reorderable()
                    ->collapsible()
                    ->defaultItems(1)
                    ->columnSpanFull()
                    ->disabled(fn($record) => $record?->status === 'completed'),
            ])
            ->columns(2);
    }
}
