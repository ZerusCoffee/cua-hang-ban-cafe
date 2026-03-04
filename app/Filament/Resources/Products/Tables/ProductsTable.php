<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProductsTable
{
     public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('images.image_path')
                    ->label('Ảnh')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(asset('images/placeholder.png'))
                    ->getStateUsing(fn ($record) => $record->images->first()?->image_path),

                TextColumn::make('name')
                    ->label('Tên sản phẩm')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Danh mục')
                    ->badge()
                    ->sortable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('cost_price')
                    ->label('Giá cost')
                    ->getStateUsing(function ($record) {
                        $total = 0;
                        if ($record->recipe && $record->recipe->recipeDetails) {
                            foreach ($record->recipe->recipeDetails as $detail) {
                                if ($detail->ingredient) {
                                    $total += floatval($detail->ingredient->cost_price) * floatval($detail->amount);
                                }
                            }
                        }
                        return round($total, 2);
                    })
                    ->numeric(decimalPlaces: 0)
                    ->suffix('đ')
                    ->color('gray')
                    ->sortable(false)
                    ->description('Tính từ nguyên liệu', 'above'),

                TextColumn::make('profit_rate')
                    ->label('Tỷ lệ lợi nhuận')
                    ->suffix('%')
                    ->sortable()
                    ->description('Tỷ lệ so với cost', 'above'),

                TextColumn::make('recommended_price')
                    ->label('Giá đề xuất (từ DB)')
                    ->numeric(decimalPlaces: 0)
                    ->suffix('đ')
                    ->color('success')
                    ->weight('bold')
                    ->sortable()
                    ->description(function ($record) {
                        $totalCost = 0;
                        if ($record->recipe && $record->recipe->recipeDetails) {
                            foreach ($record->recipe->recipeDetails as $detail) {
                                if ($detail->ingredient) {
                                    $totalCost += floatval($detail->ingredient->cost_price) * floatval($detail->amount);
                                }
                            }
                        }

                        $recommendedPrice = floatval($record->recommended_price ?? 0);
                        if ($totalCost > 0 && $recommendedPrice > 0) {
                            $profit = $recommendedPrice - $totalCost;
                            return 'Lợi nhuận: ' . number_format($profit) . 'đ';
                        }

                        return 'Chưa có dữ liệu';
                    }, position: 'above'),

                IconColumn::make('is_active')
                    ->label('Đang bán')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_featured')
                    ->label('Nổi bật')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('view_count')
                    ->label('Lượt xem')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Danh mục')
                    ->relationship('category', 'name'),

                TernaryFilter::make('is_active')
                    ->label('Đang bán')
                    ->trueLabel('Đang bán')
                    ->falseLabel('Ngừng bán'),

                TernaryFilter::make('is_featured')
                    ->label('Nổi bật')
                    ->trueLabel('Nổi bật')
                    ->falseLabel('Không nổi bật'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
