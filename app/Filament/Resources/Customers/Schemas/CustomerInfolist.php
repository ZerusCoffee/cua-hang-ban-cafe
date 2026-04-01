<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Thông tin khách hàng')
                ->columns(3)
                ->schema([
                    ImageEntry::make('avatar')
                        ->label('Ảnh đại diện')
                        ->circular()
                        ->disk('public')
                        ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name))
                        ->columnSpan(1),

                    Group::make([
                        TextEntry::make('name')
                            ->label('Tên')
                            ->weight(FontWeight::Bold),

                        TextEntry::make('email')
                            ->label('Email')
                            ->copyable(),

                        TextEntry::make('phone')
                            ->label('SĐT')
                            ->default('—'),
                    ])->columnSpan(2),
                ]),

            Section::make('Tài khoản')
                ->columns(3)
                ->schema([
                    TextEntry::make('is_locked')
                        ->label('Trạng thái')
                        ->badge()
                        ->formatStateUsing(fn($state) => $state ? 'Đã khoá' : 'Hoạt động')
                        ->color(fn($state) => $state ? 'danger' : 'success'),

                    TextEntry::make('email_verified_at')
                        ->label('Xác thực email')
                        ->getStateUsing(fn($record) => $record->email_verified_at
                            ? $record->email_verified_at->format('d/m/Y H:i')
                            : 'Chưa xác thực'
                        ),

                    TextEntry::make('created_at')
                        ->label('Ngày đăng ký')
                        ->dateTime('d/m/Y H:i'),
                ]),

            Section::make('Thống kê đơn hàng')
                ->columns(3)
                ->schema([
                    TextEntry::make('orders_count')
                        ->label('Tổng đơn')
                        ->getStateUsing(fn($record) => $record->orders()->count()),

                    TextEntry::make('orders_total')
                        ->label('Tổng chi tiêu')
                        ->getStateUsing(fn($record) => number_format($record->orders()->sum('total'), 0, ',', '.') . ' ₫'
                        ),

                    TextEntry::make('last_order_at')
                        ->label('Đơn gần nhất')
                        ->getStateUsing(fn($record) => $record->orders()->latest()->value('created_at')
                            ? \Carbon\Carbon::parse($record->orders()->latest()->value('created_at'))->format('d/m/Y H:i')
                            : '—'
                        ),
                ]),
        ]);
    }
}
