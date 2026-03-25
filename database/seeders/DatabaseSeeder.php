<?php

namespace Database\Seeders;

use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    // use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            UnitSeeder::class,
            CategorySeeder::class,
            IngredientSeeder::class,
            ImportOrderSeeder::class,
            ProductSeeder::class,
            OptionGroupSeeder::class,
            OptionSeeder::class,
            ProductOptionSeeder::class,
            ProductOptionModifierSeeder::class,
            CouponSeeder::class
        ]);
    }
}
