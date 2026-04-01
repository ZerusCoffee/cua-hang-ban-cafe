<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->disk('public')
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name)),

                TextColumn::make('name')
                    ->label('Tên')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('phone')
                    ->label('SĐT')
                    ->searchable()
                    ->default('—'),

                TextColumn::make('orders_count')
                    ->label('Đơn hàng')
                    ->counts('orders')
                    ->sortable(),

                IconColumn::make('is_locked')
                    ->label('Khoá')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('success'),

                TextColumn::make('created_at')
                    ->label('Ngày đăng ký')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_locked')
                    ->label('Trạng thái tài khoản')
                    ->trueLabel('Đã khoá')
                    ->falseLabel('Hoạt động'),

                Filter::make('created_at')
                    ->label('Ngày đăng ký')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Từ ngày'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'],  fn($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
            ])
            ->recordUrl(fn($record) => CustomerResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                Action::make('toggle_lock')
                    ->label(fn($record) => $record->is_locked ? 'Mở khoá' : 'Khoá')
                    ->icon(fn($record) => $record->is_locked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                    ->color(fn($record) => $record->is_locked ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->update(['is_locked' => !$record->is_locked])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
