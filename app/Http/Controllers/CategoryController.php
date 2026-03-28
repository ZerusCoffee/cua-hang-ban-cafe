<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryDTO;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index() {
        $categories = Category::all();

        return $this->successResponse(
            CategoryDTO::collection($categories),
            "Lấy category thành công"
        );
    }

    public function show(Category $category) {
        return $this->successResponse(
            new CategoryDTO($category),
            "Lấy category thành công"
        );
}
}
