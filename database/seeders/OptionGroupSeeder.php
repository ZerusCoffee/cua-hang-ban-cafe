<?php

namespace Database\Seeders;

use App\Models\OptionGroup;
use Illuminate\Database\Seeder;

class OptionGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            [
                'name' => 'Size',
                'min' => 1,
                'max' => 1,
                'type' => 'single',
                'is_required' => true,
            ],
            [
                'name' => 'Mức đường',
                'min' => 1,
                'max' => 1,
                'type' => 'single',
                'is_required' => true,
            ],
            [
                'name' => 'Mức đá',
                'min' => 1,
                'max' => 1,
                'type' => 'single',
                'is_required' => true,
            ],
            [
                'name' => 'Topping',
                'min' => 0,
                'max' => 5,
                'type' => 'multiple',
                'is_required' => false,
            ],
        ];

        foreach ($groups as $group) {
            OptionGroup::create($group);
        }
    }
}