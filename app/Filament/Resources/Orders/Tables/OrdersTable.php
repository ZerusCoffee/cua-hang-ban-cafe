<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->columns([

                TextColumn::make('order_number')
                    ->label('Mã đơn')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('customer.name')
                    ->label('Khách hàng')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.phone')
                    ->label('SĐT')
                    ->searchable(),

                TextColumn::make('shipping_ward')
                    ->label('Phường')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('shipping_province')
                    ->label('Tỉnh/TP')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total')
                    ->label('Tổng tiền')
                    ->money('VND')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Chờ xử lý',
                        'confirmed' => 'Đã xác nhận',
                        'delivered' => 'Đã giao',
                        'cancelled' => 'Đã huỷ',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Thanh toán')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'paid' => 'Đã thanh toán',
                        'pending' => 'Chờ thanh toán',
                        'failed' => 'Thất bại',
                        'refunded' => 'Đã hoàn tiền',
                        default => $state,
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Thời gian đặt')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])

            ->filters([

                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'pending' => 'Chờ xử lý',
                        'confirmed' => 'Đã xác nhận',
                        'delivered' => 'Đã giao',
                        'cancelled' => 'Đã huỷ',
                    ]),

                Filter::make('created_at')
                    ->label('Thời gian đặt')
                    ->form([
                        DatePicker::make('from')->label('Từ ngày'),
                        DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn ($q) => $q->whereDate('created_at', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn ($q) => $q->whereDate('created_at', '<=', $data['until'])
                            );
                    }),

                Filter::make('shipping_ward')
                    ->label('Phường giao hàng')
                    ->form([
                        TextInput::make('ward')
                            ->label('Phường'),
                    ])
                    ->query(fn (Builder $query, array $data) =>
                        $query->when(
                            $data['ward'],
                            fn ($q) => $q->where('shipping_ward', 'like', "%{$data['ward']}%")
                        )
                    ),
            ])

            ->recordUrl(fn ($record) => OrderResource::getUrl('view', ['record' => $record]))

            ->recordActions([
                Action::make('confirm')
                    ->label('Xác nhận')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(fn ($record) =>
                        $record->updateStatus(
                            'confirmed',
                            'Xác nhận bởi admin',
                            auth()->id()
                        )
                    ),

                Action::make('deliver')
                    ->label('Đã giao')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'confirmed')
                    ->requiresConfirmation()
                    ->action(fn ($record) =>
                        $record->updateStatus(
                            'delivered',
                            'Giao hàng thành công',
                            auth()->id()
                        )
                    ),

                Action::make('cancel')
                    ->label('Huỷ đơn')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) =>
                        in_array($record->status, ['pending'])
                    )
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('admin_notes')
                            ->label('Lý do huỷ')
                            ->required(),
                    ])
                    ->action(fn ($record, array $data) =>
                        $record->updateStatus(
                            'cancelled',
                            $data['admin_notes'],
                            auth()->id()
                        )
                    ),
            ])

            ->defaultSort('created_at', 'desc');
    }
}
