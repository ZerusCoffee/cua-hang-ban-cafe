<?php

namespace App\Filament\Resources\Coupons\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Thông tin cơ bản')
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Mã coupon')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('VD: WELCOME2024')
                            ->suffixAction(
                                Action::make('generate')
                                    ->label('Tạo ngẫu nhiên')
                                    ->icon('heroicon-m-arrow-path')
                                    ->action(fn(Set $set) => $set('code', strtoupper(Str::random(8))))
                            ),

                        TextInput::make('name')
                            ->label('Tên coupon')
                            ->maxLength(255)
                            ->placeholder('VD: Chào mừng khách hàng mới'),

                        Textarea::make('description')
                            ->label('Mô tả')
                            ->columnSpanFull()
                            ->rows(2),
                    ]),

                Section::make('Thông tin giảm giá')
                    ->columns(2)
                    ->schema([
                        Select::make('type')
                            ->label('Loại giảm giá')
                            ->required()
                            ->live()
                            ->options([
                                'percentage'    => 'Phần trăm (%)',
                                'fixed'         => 'Số tiền cố định (đ)',
                                'free_shipping' => 'Miễn phí vận chuyển',
                            ]),

                        TextInput::make('value')
                            ->label(fn(Get $get) => match ($get('type')) {
                                'percentage' => 'Phần trăm giảm (%)',
                                'fixed'      => 'Số tiền giảm (đ)',
                                default      => 'Giá trị',
                            })
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->hidden(fn(Get $get) => $get('type') === 'free_shipping')
                            ->suffix(fn(Get $get) => $get('type') === 'percentage' ? '%' : 'đ'),

                        TextInput::make('minimum_order_amount')
                            ->label('Đơn hàng tối thiểu (đ)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix('đ'),

                        TextInput::make('maximum_discount_amount')
                            ->label('Giảm tối đa (đ)')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('đ')
                            ->hidden(fn(Get $get) => $get('type') !== 'percentage')
                            ->helperText('Để trống nếu không giới hạn'),
                    ]),

                Section::make('Giới hạn sử dụng')
                    ->columns(2)
                    ->schema([
                        TextInput::make('usage_limit')
                            ->label('Tổng số lần dùng')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Để trống = không giới hạn'),

                        TextInput::make('usage_limit_per_customer')
                            ->label('Số lần dùng / khách hàng')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Để trống = không giới hạn'),
                    ]),

                Section::make('Thời gian hiệu lực')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Bắt đầu')
                            ->native(false)
                            ->displayFormat('d/m/Y H:i'),

                        DateTimePicker::make('expires_at')
                            ->label('Kết thúc')
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->after('starts_at'),
                    ]),

                Section::make('Trạng thái')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Kích hoạt')
                            ->default(true),
                    ]),

            ]);
    }
}
