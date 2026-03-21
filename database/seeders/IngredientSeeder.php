<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * Chỉ tạo bản ghi Ingredient với stock=0, cost_price=0.
 * Giá và tồn kho thực tế sẽ được cập nhật bởi ImportOrderSeeder
 * thông qua ImportOrder::complete().
 */
class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        $ingredients = [
            // Cơ bản
            ['name' => 'Đường trắng',    'unit' => 'Gram',       'threshold' => 1000],
            ['name' => 'Sữa tươi',        'unit' => 'Milliliter', 'threshold' => 500],
            ['name' => 'Cà phê hạt',      'unit' => 'Gram',       'threshold' => 300],
            ['name' => 'Viên phô mai',    'unit' => 'Piece',      'threshold' => 200],

            // Trà / cà phê / syrup
            ['name' => 'Trà đen',         'unit' => 'Gram',       'threshold' => 400],
            ['name' => 'Trà xanh',        'unit' => 'Gram',       'threshold' => 400],
            ['name' => 'Bột matcha',      'unit' => 'Gram',       'threshold' => 150],
            ['name' => 'Cà phê bột',      'unit' => 'Gram',       'threshold' => 300],
            ['name' => 'Siro đào',        'unit' => 'Milliliter', 'threshold' => 300],
            ['name' => 'Siro dâu',        'unit' => 'Milliliter', 'threshold' => 300],
            ['name' => 'Siro vani',       'unit' => 'Milliliter', 'threshold' => 250],
            ['name' => 'Siro caramel',    'unit' => 'Milliliter', 'threshold' => 250],

            // Sữa / nền pha chế
            ['name' => 'Sữa đặc',         'unit' => 'Gram',       'threshold' => 400],
            ['name' => 'Bột sữa',         'unit' => 'Gram',       'threshold' => 300],
            ['name' => 'Kem béo',         'unit' => 'Milliliter', 'threshold' => 300],
            ['name' => 'Whipping cream',  'unit' => 'Milliliter', 'threshold' => 200],

            // Trái cây
            ['name' => 'Đào ngâm',        'unit' => 'Gram',       'threshold' => 400],
            ['name' => 'Vải ngâm',        'unit' => 'Gram',       'threshold' => 300],
            ['name' => 'Chanh tươi',      'unit' => 'Piece',      'threshold' => 30],
            ['name' => 'Cam tươi',        'unit' => 'Piece',      'threshold' => 30],
            ['name' => 'Tắc',             'unit' => 'Piece',      'threshold' => 50],
            ['name' => 'Xoài',            'unit' => 'Gram',       'threshold' => 250],
            ['name' => 'Bơ',              'unit' => 'Gram',       'threshold' => 200],
            ['name' => 'Dâu tây',         'unit' => 'Gram',       'threshold' => 150],

            // Topping
            ['name' => 'Trân châu đen',   'unit' => 'Gram',       'threshold' => 500],
            ['name' => 'Trân châu trắng', 'unit' => 'Gram',       'threshold' => 400],
            ['name' => 'Thạch cà phê',    'unit' => 'Gram',       'threshold' => 300],
            ['name' => 'Thạch cá',        'unit' => 'Gram',       'threshold' => 300],
            ['name' => 'Thạch trái cây',  'unit' => 'Gram',       'threshold' => 300],
            ['name' => 'Pudding trứng',   'unit' => 'Piece',      'threshold' => 50],
            ['name' => 'Bánh flan',       'unit' => 'Piece',      'threshold' => 30],
            ['name' => 'Nha đam',         'unit' => 'Gram',       'threshold' => 250],
            ['name' => 'Hạt thủy tinh',   'unit' => 'Gram',       'threshold' => 250],
            ['name' => 'Kem cheese',      'unit' => 'Gram',       'threshold' => 200],
        ];

        foreach ($ingredients as $item) {
            $unit = Unit::where('name', $item['unit'])->firstOrFail();

            Ingredient::create([
                'name'       => $item['name'],
                'unit_id'    => $unit->id,
                'threshold'  => $item['threshold'],
            ]);
        }
    }
}
