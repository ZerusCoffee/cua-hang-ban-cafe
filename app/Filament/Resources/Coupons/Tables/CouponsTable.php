<?php

namespace App\Filament\Resources\Coupons\Tables;

use App\Models\Coupon;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Mã coupon')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('name')
                    ->label('Tên')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('type')
                    ->label('Loại')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'percentage' => 'Phần trăm',
                        'fixed' => 'Cố định',
                        'free_shipping' => 'Miễn ship',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'percentage' => 'info',
                        'fixed' => 'success',
                        'free_shipping' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('type_label')
                    ->label('Giá trị')
                    ->getStateUsing(fn(Coupon $record) => $record->type_label),

                TextColumn::make('used_count')
                    ->label('Đã dùng')
                    ->formatStateUsing(function (Coupon $record) {
                        $limit = $record->usage_limit ?? '∞';
                        return "{$record->used_count} / {$limit}";
                    }),

                TextColumn::make('expires_at')
                    ->label('Hết hạn')
                    ->dateTime('d/m/Y H:i')
                    ->color(fn(Coupon $record) => $record->expires_at?->isPast() ? 'danger' : 'success')
                    ->placeholder('Không giới hạn'),

                IconColumn::make('is_active')
                    ->label('Kích hoạt')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Loại')
                    ->options([
                        'percentage' => 'Phần trăm',
                        'fixed' => 'Cố định',
                        'free_shipping' => 'Miễn ship',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Trạng thái')
                    ->trueLabel('Đang hoạt động')
                    ->falseLabel('Đã tắt'),

                Filter::make('expired')
                    ->label('Đã hết hạn')
                    ->query(fn(Builder $query) => $query->where('expires_at', '<', now())),

                Filter::make('valid')
                    ->label('Còn hiệu lực')
                    ->query(fn(Builder $query) => $query->valid()),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    BulkAction::make('activate')
                        ->label('Kích hoạt')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn($records) => $records->each->update(['is_active' => true])),

                    BulkAction::make('deactivate')
                        ->label('Tắt')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn($records) => $records->each->update(['is_active' => false])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
