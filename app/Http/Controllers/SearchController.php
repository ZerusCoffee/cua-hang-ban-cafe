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
            ->take(5)
            ->get()
            ->map(fn($product) => [
                'id'    => $product->id,
                'name'  => $product->name,
                'slug'  => $product->slug,
                'price' => $product->recommended_price,
                'image' => $product->primaryImage?->url,
            ]);

        return response()->json($products);
    }
}
