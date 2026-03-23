<?php

namespace App\Filament\Resources\Reviews\Pages;

use App\Filament\Resources\Reviews\ReviewResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tất cả'),
            'pending' => Tab::make('Chờ duyệt')
                ->modifyQueryUsing(fn(Builder $query) => $query->pending())
                ->badge(fn() => \App\Models\Review::pending()->count()),
            'approved' => Tab::make('Đã duyệt')
                ->modifyQueryUsing(fn(Builder $query) => $query->approved()),
        ];
    }
}
