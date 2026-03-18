<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Option;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionModifier;
use Illuminate\Database\Seeder;

class ProductOptionModifierSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();

        foreach ($products as $product) {
            $name = mb_strtolower($product->name);

            $isCoffee = str_contains($name, 'cà phê')
                || str_contains($name, 'espresso')
                || str_contains($name, 'americano')
                || str_contains($name, 'latte')
                || str_contains($name, 'mocha')
                || str_contains($name, 'macchiato')
                || str_contains($name, 'cappuccino');

            $isMilkTea = str_contains($name, 'trà sữa');
            $isFruitDrink = str_contains($name, 'nước chanh')
                || str_contains($name, 'nước cam')
                || str_contains($name, 'sinh tố')
                || str_contains($name, 'smoothie')
                || str_contains($name, 'nước dừa');

            $productOptions = ProductOption::with('option')->where('product_id', $product->id)->get();

            foreach ($productOptions as $productOption) {
                $optionValue = $productOption->option?->value;

                if (!$optionValue) {
                    continue;
                }

                $modifiers = [];

                // Size L: tăng thêm nguyên liệu nền
                if ($optionValue === 'L') {
                    if ($isCoffee) {
                        $modifiers = [
                            'Cà phê hạt' => 5,
                            'Sữa tươi' => 30,
                            'Sữa đặc' => 10,
                            'Đường trắng' => 5,
                        ];
                    } elseif ($isMilkTea) {
                        $modifiers = [
                            'Trà đen' => 5,
                            'Bột sữa' => 10,
                            'Kem béo' => 10,
                            'Đường trắng' => 5,
                        ];
                    } elseif ($isFruitDrink) {
                        $modifiers = [
                            'Sữa tươi' => 20,
                            'Đường trắng' => 5,
                        ];
                    }
                }

                // Topping: cộng thêm đúng nguyên liệu topping
                if ($optionValue === 'Trân châu đen') {
                    $modifiers = ['Trân châu đen' => 30];
                }

                if ($optionValue === 'Trân châu trắng') {
                    $modifiers = ['Trân châu trắng' => 30];
                }

                if ($optionValue === 'Thạch cá') {
                    $modifiers = ['Thạch cá' => 30];
                }

                if ($optionValue === 'Viên phô mai') {
                    $modifiers = ['Viên phô mai' => 5];
                }

                if ($optionValue === 'Bánh flan') {
                    $modifiers = ['Bánh flan' => 1];
                }

                if ($optionValue === 'Pudding trứng') {
                    $modifiers = ['Pudding trứng' => 1];
                }

                if ($optionValue === 'Nha đam') {
                    $modifiers = ['Nha đam' => 30];
                }

                foreach ($modifiers as $ingredientName => $deltaQuantity) {
                    $ingredient = Ingredient::where('name', $ingredientName)->first();

                    if (!$ingredient) {
                        continue;
                    }

                    ProductOptionModifier::firstOrCreate(
                        [
                            'product_option_id' => $productOption->id,
                            'ingredient_id' => $ingredient->id,
                        ],
                        [
                            'delta_quantity' => $deltaQuantity,
                        ]
                    );
                }
            }
        }
    }
}