<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        $ingredients = [
            [
                'name' => 'Đường trắng',
                'unit' => 'Gram',
                'cost_price' => 18000,
                'stock' => 5000,
                'threshold' => 1000,
            ],
            [
                'name' => 'Sữa tươi',
                'unit' => 'Milliliter',
                'cost_price' => 32000,
                'stock' => 3000,
                'threshold' => 500,
            ],
            [
                'name' => 'Cà phê hạt',
                'unit' => 'Gram',
                'cost_price' => 250000,
                'stock' => 2000,
                'threshold' => 300,
            ],
            [
                'name' => 'Viên phô mai',
                'unit' => 'Piece',
                'cost_price' => 800,
                'stock' => 1000,
                'threshold' => 200,
            ],

            // Nguyên liệu trà / cà phê / syrup
            [
                'name' => 'Trà đen',
                'unit' => 'Gram',
                'cost_price' => 150000,
                'stock' => 2500,
                'threshold' => 400,
            ],
            [
                'name' => 'Trà xanh',
                'unit' => 'Gram',
                'cost_price' => 170000,
                'stock' => 2200,
                'threshold' => 400,
            ],
            [
                'name' => 'Bột matcha',
                'unit' => 'Gram',
                'cost_price' => 450000,
                'stock' => 1000,
                'threshold' => 150,
            ],
            [
                'name' => 'Cà phê bột',
                'unit' => 'Gram',
                'cost_price' => 220000,
                'stock' => 1800,
                'threshold' => 300,
            ],
            [
                'name' => 'Siro đào',
                'unit' => 'Milliliter',
                'cost_price' => 85000,
                'stock' => 2000,
                'threshold' => 300,
            ],
            [
                'name' => 'Siro dâu',
                'unit' => 'Milliliter',
                'cost_price' => 90000,
                'stock' => 1800,
                'threshold' => 300,
            ],
            [
                'name' => 'Siro vani',
                'unit' => 'Milliliter',
                'cost_price' => 95000,
                'stock' => 1500,
                'threshold' => 250,
            ],
            [
                'name' => 'Siro caramel',
                'unit' => 'Milliliter',
                'cost_price' => 100000,
                'stock' => 1500,
                'threshold' => 250,
            ],

            // Sữa / nền pha chế
            [
                'name' => 'Sữa đặc',
                'unit' => 'Gram',
                'cost_price' => 42000,
                'stock' => 2500,
                'threshold' => 400,
            ],
            [
                'name' => 'Bột sữa',
                'unit' => 'Gram',
                'cost_price' => 120000,
                'stock' => 2000,
                'threshold' => 300,
            ],
            [
                'name' => 'Kem béo',
                'unit' => 'Milliliter',
                'cost_price' => 65000,
                'stock' => 1800,
                'threshold' => 300,
            ],
            [
                'name' => 'Whipping cream',
                'unit' => 'Milliliter',
                'cost_price' => 110000,
                'stock' => 1200,
                'threshold' => 200,
            ],

            // Trái cây / thành phần phụ
            [
                'name' => 'Đào ngâm',
                'unit' => 'Gram',
                'cost_price' => 70000,
                'stock' => 2500,
                'threshold' => 400,
            ],
            [
                'name' => 'Vải ngâm',
                'unit' => 'Gram',
                'cost_price' => 75000,
                'stock' => 2000,
                'threshold' => 300,
            ],
            [
                'name' => 'Chanh tươi',
                'unit' => 'Piece',
                'cost_price' => 2500,
                'stock' => 200,
                'threshold' => 30,
            ],
            [
                'name' => 'Cam tươi',
                'unit' => 'Piece',
                'cost_price' => 3500,
                'stock' => 180,
                'threshold' => 30,
            ],
            [
                'name' => 'Tắc',
                'unit' => 'Piece',
                'cost_price' => 1500,
                'stock' => 300,
                'threshold' => 50,
            ],
            [
                'name' => 'Xoài',
                'unit' => 'Gram',
                'cost_price' => 60000,
                'stock' => 1500,
                'threshold' => 250,
            ],
            [
                'name' => 'Bơ',
                'unit' => 'Gram',
                'cost_price' => 80000,
                'stock' => 1200,
                'threshold' => 200,
            ],
            [
                'name' => 'Dâu tây',
                'unit' => 'Gram',
                'cost_price' => 120000,
                'stock' => 1000,
                'threshold' => 150,
            ],

            // Topping
            [
                'name' => 'Trân châu đen',
                'unit' => 'Gram',
                'cost_price' => 45000,
                'stock' => 3000,
                'threshold' => 500,
            ],
            [
                'name' => 'Trân châu trắng',
                'unit' => 'Gram',
                'cost_price' => 50000,
                'stock' => 2500,
                'threshold' => 400,
            ],
            [
                'name' => 'Thạch cà phê',
                'unit' => 'Gram',
                'cost_price' => 38000,
                'stock' => 2000,
                'threshold' => 300,
            ],
            [
                'name' => 'Thạch cá',
                'unit' => 'Gram',
                'cost_price' => 55000,
                'stock' => 1800,
                'threshold' => 300,
            ],
            [
                'name' => 'Thạch trái cây',
                'unit' => 'Gram',
                'cost_price' => 50000,
                'stock' => 1800,
                'threshold' => 300,
            ],
            [
                'name' => 'Pudding trứng',
                'unit' => 'Piece',
                'cost_price' => 3500,
                'stock' => 300,
                'threshold' => 50,
            ],
            [
                'name' => 'Bánh flan',
                'unit' => 'Piece',
                'cost_price' => 6000,
                'stock' => 200,
                'threshold' => 30,
            ],
            [
                'name' => 'Nha đam',
                'unit' => 'Gram',
                'cost_price' => 40000,
                'stock' => 1500,
                'threshold' => 250,
            ],
            [
                'name' => 'Hạt thủy tinh',
                'unit' => 'Gram',
                'cost_price' => 52000,
                'stock' => 1500,
                'threshold' => 250,
            ],
            [
                'name' => 'Kem cheese',
                'unit' => 'Gram',
                'cost_price' => 95000,
                'stock' => 1200,
                'threshold' => 200,
            ],
        ];

        foreach ($ingredients as $item) {
            $unit = Unit::where('name', $item['unit'])->firstOrFail();

            Ingredient::create([
                'name' => $item['name'],
                'unit_id' => $unit->id,
                'cost_price' => $item['cost_price'],
                'stock' => $item['stock'],
                'threshold' => $item['threshold'],
            ]);
        }
    }
}