<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function suggest(Request $request): JsonResponse
    {
        $query = $request->input('q');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $products = Product::search($query)
            ->query(fn($q) => $q->active())
            ->take(5)
            ->get()
            ->map(fn($product) => [
                'id'    => $product->id,
                'name'  => $product->name,
                'slug'  => $product->slug,
                'category' => $product->category->name,
                'short_description' => $product->short_description,
                'price' => $product->recommended_price,
                'image' => $product->primaryImage?->image_path?
                    asset('storage/' . $product->primaryImage->image_path) : null,
                'is_featured' => $product->is_featured,
                'is_active' => $product->is_active,
            ]);

        return response()->json($products);
    }
}
