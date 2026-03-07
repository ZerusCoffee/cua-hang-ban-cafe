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
}
