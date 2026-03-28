<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Database\Eloquent\Collection;

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
                    'max' => $group->first()->option->group->max,
                    'isRequired' => $group->first()->option->group->is_required,
                    'options' => $group->map(function ($item) {
                        return [
                            'id' => $item->option->id,
                            'productOptionId' => $item->id,
                            'value' => $item->option->value,
                            'additionalPrice' => $item->additional_price
                        ];
                    })->values()
                ];
            })
            ->values();
    }


    public function getRelatedProducts(Product $product)
{
    // Lấy sản phẩm cùng category (ưu tiên)
    $relatedProducts = Product::where('category_id', $product->category_id)
        ->where('id', '!=', $product->id)
        ->inRandomOrder()
        ->limit(5) // Giới hạn 5 sản phẩm cùng category
        ->get();
    
    // Nếu chưa đủ 8 sản phẩm, lấy thêm sản phẩm bất kỳ
    if ($relatedProducts->count() < 8) {
        $needCount = 8 - $relatedProducts->count();
        
        $randomProducts = Product::where('id', '!=', $product->id)
            ->whereNotIn('id', $relatedProducts->pluck('id'))
            ->inRandomOrder()
            ->limit($needCount)
            ->get();
        
        $relatedProducts = $relatedProducts->concat($randomProducts);
    }
    
    return $relatedProducts;
}  

    public function attachStockStatus(Collection $products): Collection
    {
        $products->loadMissing('recipeDetails');

        $allIngredientIds = $products
            ->flatMap(fn($p) => $p->recipeDetails->pluck('ingredient_id'))
            ->unique()
            ->toArray();

        if (empty($allIngredientIds)) {
            return $products->each(fn($p) => $p->in_stock = true);
        }

        $ingredients = Ingredient::whereIn('id', $allIngredientIds)
            ->get()
            ->keyBy('id');

        return $products->each(function ($product) use ($ingredients) {
            $product->in_stock = $product->recipeDetails->every(
                fn($detail) => isset($ingredients[$detail->ingredient_id]) &&
                    floatval($ingredients[$detail->ingredient_id]->stock) >= floatval($detail->amount)
            );
        });
    }
}
