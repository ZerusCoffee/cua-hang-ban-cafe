<?php
namespace Database\Seeders;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get categories or create if they dont exist
        $coffeeCategory = Category::firstOrCreate(
            ['name' => 'Cà Phê'],
            ['slug' => 'ca-phe']
        );

        $beverageCategory = Category::firstOrCreate(
            ['name' => 'Đồ Uống'],
            ['slug' => 'do-uong']
        );
        // Create coffee products
        Product::factory()
            ->count(5)
            ->coffee()
            ->create([
                'category_id' => $coffeeCategory->id,
            ]);
        // Create beverage products
        Product::factory()
            ->count(5)
            ->beverage()
            ->create([
                'category_id' => $beverageCategory->id,
            ]);
    }
}
