<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductCollectionDTO;
use App\Http\Resources\ProductDTO;
use App\Http\Resources\ProductListDTO;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * List products
     */
   public function index(Request $request)
{
    $query = Product::query();

    // filter category
    $query->when(
        $request->filled('category_id'),
        fn ($q) => $q->where('category_id', $request->category_id)
    );

    // search name
    $query->when(
        $request->filled('name'),
        fn ($q) => $q->where('name', 'like', '%' . $request->name . '%')
    );

    // price range
    $query->when(
        $request->filled('min_price'),
        fn ($q) => $q->where('recommended_price', '>=', $request->min_price)
    );

    $query->when(
        $request->filled('max_price'),
        fn ($q) => $q->where('recommended_price', '<=', $request->max_price)
    );

    //limit, max = 50
    $limit = (int) $request->get('limit', 8);
    $limit = min($limit, 50);

    $products = $query
        ->orderBy('id')
        ->paginate($limit);

    return $this->successResponse(
        new ProductCollectionDTO($products),
        "Lấy danh sách sản phẩm thành công"
    );
}

    public function getAllFeatured()
    {
        try {
            $products = Product::active()
                ->featured()
                ->paginate(10);

            return $this->successResponse(
                ProductListDTO::collection($products),
                "Lấy danh sách sản phẩm thành công"
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                "Có lỗi xảy ra khi lấy danh sách sản phẩm: " . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Show product
     */
    public function show(Product $product)
    {
        try {
            return $this->successResponse(
                new ProductDTO($product),
                "Lấy chi tiết sản phẩm thành công"
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                "Có lỗi xảy ra: " . $e->getMessage(),
                500
            );
        }
    }

    public function getByCategory($categoryId) {
        $products = Product::active()
        ->where('category_id', $categoryId)
        ->get();

        return $this->successResponse(  
            ProductListDTO::collection($products),
            "Lấy danh sách sản phẩm theo Category thành công"
        );
    }

    public function getNewest(){
        try {
            $products = Product::active()
                ->latest()
                ->paginate(10);

            return $this->successResponse(
                ProductListDTO::collection($products),
                "Lấy danh sách sản phẩm mới nhất thành công"
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                "Có lỗi xảy ra khi lấy danh sách sản phẩm: " . $e->getMessage(),
                500
            );
        }
    }

    public function getMaxPrice()
{
    try {
        $maxPrice = Product::active()->max('recommended_price');

        return $this->successResponse(
            $maxPrice,
            "Lấy giá cao nhất thành công"
        );
    } catch (\Exception $e) {
        return $this->errorResponse(
            "Có lỗi xảy ra: " . $e->getMessage(),
            500
        );
    }
}
}
