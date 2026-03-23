<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Models\Coupon;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CouponInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Thông tin cơ bản')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('code')
                            ->label('Mã coupon')
                            ->badge()
                            ->color('primary')
                            ->copyable(),

                        TextEntry::make('name')
                            ->label('Tên coupon'),

                        TextEntry::make('description')
                            ->label('Mô tả')
                            ->columnSpanFull(),
                    ]),

                Section::make('Thông tin giảm giá')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('type_label')
                            ->label('Loại & Giá trị'),

                        TextEntry::make('minimum_order_amount')
                            ->label('Đơn tối thiểu')
                            ->money('VND'),

                        TextEntry::make('maximum_discount_amount')
                            ->label('Giảm tối đa')
                            ->money('VND')
                            ->placeholder('Không giới hạn'),
                    ]),

                Section::make('Giới hạn & Thời gian')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('used_count')
                            ->label('Đã sử dụng')
                            ->formatStateUsing(fn(Coupon $record) => "{$record->used_count} / " . ($record->usage_limit ?? '∞')),

                        TextEntry::make('usage_limit_per_customer')
                            ->label('Giới hạn / khách')
                            ->placeholder('Không giới hạn'),

                        TextEntry::make('starts_at')
                            ->label('Bắt đầu')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Không giới hạn'),

                        TextEntry::make('expires_at')
                            ->label('Hết hạn')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Không giới hạn'),
                    ]),

                Section::make('Lịch sử sử dụng')
                    ->schema([
                        RepeatableEntry::make('usages')
                            ->label('')
                            ->schema([
                                TextEntry::make('customer.name')
                                    ->label('Khách hàng'),

                                TextEntry::make('order.id')
                                    ->label('Đơn hàng #'),

                                TextEntry::make('discount_amount')
                                    ->label('Số tiền giảm')
                                    ->money('VND'),

                                TextEntry::make('created_at')
                                    ->label('Thời gian')
                                    ->dateTime('d/m/Y H:i'),
                            ])
                            ->columns(4),
                    ]),

            ]);
    }
}
