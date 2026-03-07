<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Thông tin đơn hàng')
                ->columns(3)
                ->schema([
                    TextEntry::make('order_number')
                        ->label('Mã đơn'),

                    TextEntry::make('status')
                        ->label('Trạng thái')
                        ->badge()
                        ->color(fn(string $state) => match ($state) {
                            'pending'   => 'warning',
                            'confirmed' => 'info',
                            'delivered' => 'success',
                            'cancelled' => 'danger',
                            default     => 'gray',
                        })
                        ->formatStateUsing(fn(string $state) => match ($state) {
                            'pending'   => 'Chờ xử lý',
                            'confirmed' => 'Đã xác nhận',
                            'delivered' => 'Đã giao',
                            'cancelled' => 'Đã huỷ',
                            default     => $state,
                        }),

                    TextEntry::make('payment_status')
                        ->label('Thanh toán')
                        ->badge()
                        ->color(fn(string $state) => match ($state) {
                            'paid'     => 'success',
                            'pending'  => 'warning',
                            'failed'   => 'danger',
                            'refunded' => 'gray',
                            default    => 'gray',
                        })
                        ->formatStateUsing(fn(string $state) => match ($state) {
                            'paid'     => 'Đã thanh toán',
                            'pending'  => 'Chờ thanh toán',
                            'failed'   => 'Thất bại',
                            'refunded' => 'Đã hoàn tiền',
                            default    => $state,
                        }),

                    TextEntry::make('created_at')
                        ->label('Thời gian đặt')
                        ->dateTime('d/m/Y H:i'),

                    TextEntry::make('payment_method')
                        ->label('Phương thức thanh toán'),

                    TextEntry::make('customer_notes')
                        ->label('Ghi chú khách hàng')
                        ->default('—'),
                ]),

            Section::make('Thông tin giao hàng')
                ->columns(2)
                ->schema([
                    TextEntry::make('shipping_full_name')->label('Người nhận'),
                    TextEntry::make('shipping_phone')->label('Số điện thoại'),
                    TextEntry::make('full_address')
                        ->label('Địa chỉ')
                        ->getStateUsing(fn($record) => $record->full_address),
                ]),

            Section::make('Sản phẩm đặt')
                ->schema([
                    RepeatableEntry::make('items')
                        ->label('')
                        ->schema([
                            TextEntry::make('product_name')->label('Sản phẩm'),
                            TextEntry::make('product_sku')->label('SKU'),
                            TextEntry::make('quantity')->label('Số lượng'),
                            TextEntry::make('price')->label('Đơn giá')->money('VND'),
                            TextEntry::make('subtotal')->label('Thành tiền')->money('VND'),
                            TextEntry::make('options')
                                ->label('Tuỳ chọn')
                                ->getStateUsing(fn($record) =>
                                    collect($record->options ?? [])
                                        ->map(fn($o) => ($o['group_name'] ?? '') . ': ' . ($o['option_value'] ?? ''))
                                        ->filter()
                                        ->join(', ') ?: '—'
                                ),
                        ])
                        ->columns(6),
                ]),

            Section::make('Tổng tiền')
                ->columns(3)
                ->schema([
                    TextEntry::make('subtotal')->label('Tiền hàng')->money('VND'),
                    TextEntry::make('discount_amount')->label('Giảm giá')->money('VND'),
                    TextEntry::make('shipping_fee')->label('Phí vận chuyển')->money('VND'),
                    TextEntry::make('tax_amount')->label('Thuế')->money('VND'),
                    TextEntry::make('total')
                        ->label('Tổng thanh toán')
                        ->money('VND')
                        ->weight(FontWeight::Bold),
                ]),

            Section::make('Log lợi nhuận')
                ->visible(fn($record) => in_array($record->status, ['confirmed', 'delivered']))
                ->schema([
                    RepeatableEntry::make('profitLogs')
                        ->label('')
                        ->schema([
                            TextEntry::make('product_name')->label('Sản phẩm'),
                            TextEntry::make('quantity')->label('SL'),
                            TextEntry::make('unit_price')->label('Giá bán')->money('VND'),
                            TextEntry::make('unit_cost')->label('Giá cost')->money('VND'),
                            TextEntry::make('unit_profit')->label('LN/sp')->money('VND'),
                            TextEntry::make('total_profit')->label('Tổng LN')->money('VND'),
                            TextEntry::make('profit_margin')->label('Biên LN')->suffix('%'),
                            TextEntry::make('logged_at')
                                ->label('Thời điểm xác nhận')
                                ->dateTime('d/m/Y H:i'),
                        ])
                        ->columns(8),
                ]),

            Section::make('Lịch sử trạng thái')
                ->schema([
                    RepeatableEntry::make('statusHistories')
                        ->label('')
                        ->schema([
                            TextEntry::make('status')->label('Trạng thái'),
                            TextEntry::make('notes')->label('Ghi chú')->default('—'),
                            TextEntry::make('user.name')->label('Người thực hiện')->default('Hệ thống'),
                            TextEntry::make('created_at')
                                ->label('Thời gian')
                                ->dateTime('d/m/Y H:i'),
                        ])
                        ->columns(4),
                ]),
        ]);
    }
}
