<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Thông tin đánh giá')
                    ->columns(2)
                    ->schema([
                        Select::make('product_id')
                            ->label('Sản phẩm')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(),

                        Select::make('customer_id')
                            ->label('Khách hàng')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(),

                        Select::make('order_id')
                            ->label('Đơn hàng')
                            ->relationship('order', 'id')
                            ->required()
                            ->disabled(),

                        Select::make('rating')
                            ->label('Số sao')
                            ->options([
                                1 => '★ 1 sao',
                                2 => '★★ 2 sao',
                                3 => '★★★ 3 sao',
                                4 => '★★★★ 4 sao',
                                5 => '★★★★★ 5 sao',
                            ])
                            ->required(),
                    ]),

                Section::make('Nội dung')
                    ->schema([
                        TextInput::make('title')
                            ->label('Tiêu đề')
                            ->maxLength(255),

                        Textarea::make('comment')
                            ->label('Bình luận')
                            ->rows(4),

                        FileUpload::make('images')
                            ->label('Hình ảnh')
                            ->image()
                            ->multiple()
                            ->maxFiles(5)
                            ->directory('reviews')
                            ->disabled(), // admin không upload lại ảnh
                    ]),

                Section::make('Trạng thái')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_verified_purchase')
                            ->label('Đã xác minh mua hàng')
                            ->disabled(),

                        Toggle::make('is_approved')
                            ->label('Duyệt hiển thị'),
                    ]),

            ]);
    }
}
