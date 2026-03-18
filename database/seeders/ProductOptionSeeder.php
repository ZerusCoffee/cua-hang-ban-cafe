<?php

namespace Database\Seeders;

use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Database\Seeder;

class ProductOptionSeeder extends Seeder
{
    public function run(): void
    {
        $optionMap = [
            'Size' => [
                'M' => 0,
                'L' => 5000,
            ],
            'Mức đường' => [
                '0%' => 0,
                '30%' => 0,
                '50%' => 0,
                '70%' => 0,
                '100%' => 0,
            ],
            'Mức đá' => [
                'Không đá' => 0,
                'Ít đá' => 0,
                'Bình thường' => 0,
            ],
            'Topping' => [
                'Trân châu đen' => 8000,
                'Trân châu trắng' => 8000,
                'Thạch cá' => 10000,
                'Viên phô mai' => 12000,
                'Bánh flan' => 12000,
                'Pudding trứng' => 10000,
                'Nha đam' => 9000,
            ],
        ];

        $snackKeywords = [
            'Khoai tây',
            'Bánh tráng',
            'Bò khô',
            'Khô gà',
            'Khô bò',
            'Hạt dẻ',
            'Hạt điều',
            'Hạnh nhân',
            'Bánh quy',
            'Bánh quế',
            'Snack',
            'Bim bim',
            'Mực khô',
            'Chà bông',
        ];

        $products = Product::all();

        foreach ($products as $product) {
            $isSnack = false;

            foreach ($snackKeywords as $keyword) {
                if (str_contains($product->name, $keyword)) {
                    $isSnack = true;
                    break;
                }
            }

            if ($isSnack) {
                continue;
            }

            foreach ($optionMap as $groupName => $options) {
                $group = OptionGroup::where('name', $groupName)->first();

                if (!$group) {
                    continue;
                }

                foreach ($options as $optionValue => $additionalPrice) {
                    $option = Option::where('group_id', $group->id)
                        ->where('value', $optionValue)
                        ->first();

                    if (!$option) {
                        continue;
                    }

                    ProductOption::firstOrCreate([
                        'product_id' => $product->id,
                        'option_id' => $option->id,
                    ], [
                        'additional_price' => $additionalPrice,
                    ]);
                }
            }
        }
    }
}