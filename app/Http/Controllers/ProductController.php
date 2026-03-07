<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductDTO;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * List products
     */
    public function index()
    {
        try {
            $products = Product::active()->paginate(10);

            return $this->successResponse(  
                ProductDTO::collection($products),
                "Lấy danh sách sản phẩm thành công"
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                "Có lỗi xảy ra khi lấy danh sách sản phẩm: " . $e->getMessage(),
                500
            );
        }
    }

    public function getAllFeatured()
    {
        try {
            $products = Product::active()
                ->featured()
                ->paginate(10);

            return $this->successResponse(
                ProductDTO::collection($products),
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
        $products = Product::where('category_id', $categoryId)->get();

        return $this->successResponse(  
            ProductDTO::collection($products),
            "Lấy danh sách sản phẩm theo Category thành công"
        );
    }
}
