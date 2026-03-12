<?php

namespace App\Services;

use App\Models\ProductOption;

class ProductService
{
    public function getProductOptions($productId)
    {
        $options = ProductOption::with([
        'option.group'
    ])
    ->where('product_id', $productId)
    ->get();
    
    return $options
        ->groupBy('option.group.id')
        ->map(function ($group) {
            return [
                'groupId' => $group->first()->option->group->id,
                'groupName' => $group->first()->option->group->name,
                'type' => $group->first()->option->group->type,
                'min' => $group->first()->option->group->min,
                'max' => $group->first()->option-> group->max,
                'isRequired' => $group->first()->option->group->is_required,
                'options' => $group->map(function ($item) {
                    return [
                        'id' => $item->option->id,
                        'value' => $item->option->value,
                        'additionalPrice' => $item->additional_price
                    ];
                })->values()
            ];
        })
        ->values();
    }

}