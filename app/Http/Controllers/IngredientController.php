<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IngredientController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:70|unique:ingredients,name',
            'unit_id' => 'required|exists:units,id',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|numeric|min:0',
            'threshold' => 'nullable|numeric|min:0'
        ]);

        $ingredient = Ingredient::create($validated);
        $ingredient->refresh();

        return response()->json([
            'success' => true,
            'data' => $ingredient
        ], 201);
    }

    public function index(Request $request)
    {
        $ingredients = Ingredient::query()
            ->when($request->filled('search'), fn($q) => $q->search($request->search))
            ->when(
                $request->filled('unit_id'),
                fn($q) =>
                $q->unit($request->unit_id)
            )
            ->when(
                $request->boolean('low_stock'),
                fn($q) =>
                $q->lowStock()
            )
            ->paginate(10)
            ->withQueryString();


        return response()->json([
            'success' => true,
            'data' => $ingredients
        ]);
    }

    public function show(Ingredient $ingredient)
    {
        return response()->json([
            'success' => true,
            'data' => $ingredient
        ]);
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:70',
                Rule::unique('ingredients', 'name')->ignore($ingredient->id)
            ],
            'unit_id' => 'required|exists:units,id',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|numeric|min:0',
            'threshold' => 'nullable|numeric|min:0'
        ]);

        $ingredient->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật nguyên liệu thành công',
            'data' => $ingredient
        ]);
    }


    // Làm tạm, chưa có đúng nghiệp vụ
    public function destroy(Ingredient $ingredient)
    {
        $ingredient->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa nguyên liệu thành công'
        ]);
    }
}
