<?php

namespace Database\Seeders;

use App\Models\Option;
use App\Models\OptionGroup;
use Illuminate\Database\Seeder;

class OptionSeeder extends Seeder
{
    public function run(): void
    {
        $sizeGroup = OptionGroup::where('name', 'Size')->first();
        $sugarGroup = OptionGroup::where('name', 'Mức đường')->first();
        $iceGroup = OptionGroup::where('name', 'Mức đá')->first();
        $toppingGroup = OptionGroup::where('name', 'Topping')->first();

        $options = [
            // Size
            ['group_id' => $sizeGroup->id, 'value' => 'M'],
            ['group_id' => $sizeGroup->id, 'value' => 'L'],

            // Mức đường
            ['group_id' => $sugarGroup->id, 'value' => '0%'],
            ['group_id' => $sugarGroup->id, 'value' => '30%'],
            ['group_id' => $sugarGroup->id, 'value' => '50%'],
            ['group_id' => $sugarGroup->id, 'value' => '70%'],
            ['group_id' => $sugarGroup->id, 'value' => '100%'],

            // Mức đá
            ['group_id' => $iceGroup->id, 'value' => 'Không đá'],
            ['group_id' => $iceGroup->id, 'value' => 'Ít đá'],
            ['group_id' => $iceGroup->id, 'value' => 'Bình thường'],

            // Topping
            ['group_id' => $toppingGroup->id, 'value' => 'Trân châu đen'],
            ['group_id' => $toppingGroup->id, 'value' => 'Trân châu trắng'],
            ['group_id' => $toppingGroup->id, 'value' => 'Thạch cá'],
            ['group_id' => $toppingGroup->id, 'value' => 'Viên phô mai'],
            ['group_id' => $toppingGroup->id, 'value' => 'Bánh flan'],
        ];

        foreach ($options as $option) {
            Option::create($option);
        }
    }
}