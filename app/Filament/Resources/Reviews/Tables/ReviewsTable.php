<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Models\Review;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Khách hàng')
                    ->searchable(),

                TextColumn::make('product.name')
                    ->label('Sản phẩm')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('rating')
                    ->label('Sao')
                    ->formatStateUsing(fn($state) => str_repeat('★', $state))
                    ->color(fn($state) => match (true) {
                        $state >= 4 => 'success',
                        $state == 3 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('title')
                    ->label('Tiêu đề')
                    ->limit(40)
                    ->placeholder('—'),

                IconColumn::make('is_verified_purchase')
                    ->label('Xác minh')
                    ->boolean(),

                IconColumn::make('is_approved')
                    ->label('Đã duyệt')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Ngày đánh giá')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('rating')
                    ->label('Số sao')
                    ->options([
                        1 => '1 sao',
                        2 => '2 sao',
                        3 => '3 sao',
                        4 => '4 sao',
                        5 => '5 sao',
                    ]),

                TernaryFilter::make('is_approved')
                    ->label('Trạng thái duyệt')
                    ->trueLabel('Đã duyệt')
                    ->falseLabel('Chờ duyệt'),

                TernaryFilter::make('is_verified_purchase')
                    ->label('Xác minh mua hàng')
                    ->trueLabel('Đã xác minh')
                    ->falseLabel('Chưa xác minh'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('approve')
                    ->label('Duyệt')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Review $record) => !$record->is_approved)
                    ->action(fn(Review $record) => $record->update(['is_approved' => true])),

                Action::make('reject')
                    ->label('Ẩn')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Review $record) => $record->is_approved)
                    ->action(fn(Review $record) => $record->update(['is_approved' => false])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label('Duyệt tất cả')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn($records) => $records->each->update(['is_approved' => true])),

                    BulkAction::make('reject')
                        ->label('Ẩn tất cả')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn($records) => $records->each->update(['is_approved' => false])),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
