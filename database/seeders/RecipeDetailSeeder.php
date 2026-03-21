<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\RecipeDetail;
use Illuminate\Database\Seeder;

class RecipeDetailSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();

        foreach ($products as $product) {
            $recipe = $this->getRecipeByProductName($product->name);

            if (empty($recipe)) {
                continue;
            }

            foreach ($recipe as $ingredientName => $amount) {
                $ingredient = Ingredient::where('name', $ingredientName)->first();

                if (!$ingredient) {
                    continue;
                }

                RecipeDetail::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'ingredient_id' => $ingredient->id,
                    ],
                    [
                        'amount' => $amount,
                    ]
                );
            }
        }
    }

    private function getRecipeByProductName(string $productName): array
    {
        $name = mb_strtolower($productName);

        // Cà phê đen
        if (str_contains($name, 'cà phê đen')) {
            return [
                'Cà phê hạt' => 18,
                'Đường trắng' => 10,
            ];
        }

        // Cà phê sữa
        if (str_contains($name, 'cà phê sữa')) {
            return [
                'Cà phê hạt' => 18,
                'Sữa đặc' => 25,
                'Đường trắng' => 5,
            ];
        }

        // Espresso
        if (str_contains($name, 'espresso')) {
            return [
                'Cà phê hạt' => 20,
            ];
        }

        // Americano
        if (str_contains($name, 'americano')) {
            return [
                'Cà phê hạt' => 18,
            ];
        }

        // Cappuccino
        if (str_contains($name, 'cappuccino')) {
            return [
                'Cà phê hạt' => 18,
                'Sữa tươi' => 120,
                'Whipping cream' => 20,
                'Đường trắng' => 8,
            ];
        }

        // Latte
        if (str_contains($name, 'latte')) {
            return [
                'Cà phê hạt' => 18,
                'Sữa tươi' => 150,
                'Đường trắng' => 8,
            ];
        }

        // Mocha
        if (str_contains($name, 'mocha')) {
            return [
                'Cà phê hạt' => 18,
                'Sữa tươi' => 120,
                'Siro caramel' => 15,
                'Đường trắng' => 8,
            ];
        }

        // Macchiato
        if (str_contains($name, 'macchiato')) {
            return [
                'Cà phê hạt' => 18,
                'Sữa tươi' => 80,
                'Kem béo' => 20,
                'Siro vani' => 10,
            ];
        }

        // Trà xanh sữa
        if (str_contains($name, 'trà xanh sữa')) {
            return [
                'Trà xanh' => 15,
                'Bột sữa' => 25,
                'Đường trắng' => 20,
                'Kem béo' => 15,
            ];
        }

        // Trà đen Macchiato
        if (str_contains($name, 'trà đen macchiato')) {
            return [
                'Trà đen' => 15,
                'Đường trắng' => 20,
                'Kem béo' => 30,
                'Whipping cream' => 20,
                'Sữa tươi' => 20,
            ];
        }

        // Matcha Latte
        if (str_contains($name, 'matcha latte')) {
            return [
                'Bột matcha' => 10,
                'Sữa tươi' => 120,
                'Đường trắng' => 15,
            ];
        }

        // Trà đào cam sả
        if (str_contains($name, 'trà đào cam sả')) {
            return [
                'Trà đen' => 10,
                'Siro đào' => 20,
                'Đường trắng' => 15,
                'Đào ngâm' => 30,
                'Cam tươi' => 1,
            ];
        }

        // Trà dâu tươi
        if (str_contains($name, 'trà dâu tươi')) {
            return [
                'Trà đen' => 10,
                'Siro dâu' => 15,
                'Dâu tây' => 50,
                'Đường trắng' => 20,
            ];
        }

        // Sữa chua đá xay
        if (str_contains($name, 'sữa chua đá xay')) {
            return [
                'Sữa tươi' => 50,
                'Sữa đặc' => 30,
                'Đường trắng' => 10,
            ];
        }

        // Nước chanh
        if (str_contains($name, 'nước chanh')) {
            return [
                'Chanh tươi' => 2,
                'Đường trắng' => 20,
            ];
        }

        // Nước ép dâu tây
        if (str_contains($name, 'nước ép dâu tây')) {
            return [
                'Dâu tây' => 150,
                'Đường trắng' => 20,
            ];
        }

        // Nước cam
        if (str_contains($name, 'nước cam')) {
            return [
                'Cam tươi' => 2,
                'Đường trắng' => 15,
            ];
        }

        // Sinh tố xoài
        if (str_contains($name, 'sinh tố xoài')) {
            return [
                'Xoài' => 120,
                'Sữa tươi' => 80,
                'Đường trắng' => 15,
            ];
        }

        // Sinh tố bơ
        if (str_contains($name, 'sinh tố bơ')) {
            return [
                'Bơ' => 120,
                'Sữa tươi' => 100,
                'Đường trắng' => 15,
            ];
        }

        // Bánh flan
        if (str_contains($name, 'bánh flan')) {
            return [
                'Bánh flan' => 1,
            ];
        }

        // Pudding trứng
        if (str_contains($name, 'pudding trứng')) {
            return [
                'Pudding trứng' => 1,
            ];
        }

        return [];
    }
}
