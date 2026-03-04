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
        ];

        foreach ($ingredients as $item) {

            $unit = Unit::where('name', $item['unit'])->first();

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
