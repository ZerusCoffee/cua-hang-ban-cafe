<?php
namespace Database\Seeders;
use App\Models\Category;
use Illuminate\Database\Seeder;
class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Cà Phê', 'slug' => 'ca-phe'],
            ['name' => 'Đồ Uống', 'slug' => 'do-uong'],
            ['name' => 'Bánh', 'slug' => 'banh'],
            ['name' => 'Snack', 'slug' => 'snack'],
            ['name' => 'Khác', 'slug' => 'khac']
        ];
        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                ['slug' => $category['slug']]
            );
        }
    }
}
