<?php
namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\RecipeDetail;
use App\Models\Ingredient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coffeeCategory = Category::where('slug', 'ca-phe')->first();
        $beverageCategory = Category::where('slug', 'do-uong')->first();
        $snackCategory = Category::where('slug', 'snack')->first();

        // Cà phê
        $coffees = [
            'Cà phê đen nóng', 'Cà phê đen đá', 'Cà phê sữa nóng', 'Cà phê sữa đá',
            'Cappuccino nóng', 'Cappuccino đá', 'Latte nóng', 'Latte đá',
            'Espresso nóng', 'Espresso đá', 'Americano nóng', 'Americano đá',
            'Mocha nóng', 'Mocha đá', 'Macchiato nóng', 'Macchiato đá'
        ];

        foreach ($coffees as $name) {
            $this->createProduct($coffeeCategory->id, $name, 'Đồ uống cà phê thơm ngon', 'Một thức uống với hương vị đậm đà và đặc trưng của cà phê nguyên chất, mang đến sự tỉnh táo cho một ngày làm việc hiệu quả.');
        }

        // Đồ uống
        $beverages = [
            'Trà đen Macchiato', 'Trà xanh sữa', 'Matcha Latte',
            'Trà đào cam sả', 'Trà dâu tươi', 'Sữa chua đá xay',
            'Sinh tố xoài', 'Sinh tố bơ', 'Nước ép dâu tây'
        ];

        foreach ($beverages as $name) {
            $this->createProduct($beverageCategory->id, $name, 'Đồ uống tươi mát', 'Được pha chế từ nguyên liệu tươi ngon nhất, giữ nguyên hương vị tự nhiên và đem lại cảm giác sảng khoái tức thì.');
        }

        // Snack
        $snacks = [
            'Bánh flan', 'Pudding trứng', 'Bánh quy bơ', 'Bánh quy socola', 'Hạt hướng dương'
        ];

        foreach ($snacks as $name) {
            if ($snackCategory) {
                $this->createProduct($snackCategory->id, $name, 'Đồ ăn vặt ngọt ngào', 'Món ăn kèm hoàn hảo cho ly nước của bạn thêm phần trọn vẹn.');
            }
        }
    }

    private function createProduct(int $categoryId, string $name, string $shortDesc, string $desc): void
    {
        // Tạm thời tạo Product chưa có profit_rate và price
        $product = Product::create([
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => Str::slug($name),
            'recommended_price' => 0,
            'profit_rate' => 0,
            'short_description' => $shortDesc,
            'description' => $desc,
            'is_featured' => (bool)rand(0, 1),
            'is_active' => true,
            'view_count' => rand(10, 500)
        ]);

        // Tạo recipe cho product ngay lúc này để tính cost_price
        $recipe = $this->getRecipeByProductName($name);

        foreach ($recipe as $ingredientName => $amount) {
            $ingredient = Ingredient::where('name', $ingredientName)->first();

            if ($ingredient) {
                RecipeDetail::create([
                    'product_id' => $product->id,
                    'ingredient_id' => $ingredient->id,
                    'amount' => $amount,
                ]);
            }
        }

        // Tính cost price
        $costPrice = $product->cost_price;

        // Random profit_rate (ví dụ từ 30% đến 70%)
        $profitRate = rand(30, 70);

        if ($costPrice > 0) {
            // Giá tính theo tỷ suất lợi nhuận
            $calculatedPrice = $costPrice * (1 + $profitRate / 100);

            // Làm tròn giá lên đến hàng nghìn (VD: 21300 -> 22000, 21000 -> 21000)
            $recommendedPrice = ceil($calculatedPrice / 1000) * 1000;

            // Do làm tròn lên, tỷ suất lợi nhuận thực tế sẽ thay đổi
            // Cập nhật lại profit_rate cho chính xác với giá đã làm tròn
            $realProfitRate = round((($recommendedPrice - $costPrice) / $costPrice) * 100, 2);
        } else {
            // Nếu không có công thức, set giá mặc định
            $recommendedPrice = rand(20, 60) * 1000;
            $realProfitRate = $profitRate;
        }

        // Cập nhật lại giá và profit_rate
        $product->update([
            'recommended_price' => $recommendedPrice,
            'profit_rate' => $realProfitRate,
        ]);
    }

    private function getRecipeByProductName(string $productName): array
    {
        $name = mb_strtolower($productName);

        if (str_contains($name, 'cà phê đen')) {
            return ['Cà phê hạt' => 18, 'Đường trắng' => 10];
        }
        if (str_contains($name, 'cà phê sữa')) {
            return ['Cà phê hạt' => 18, 'Sữa đặc' => 25, 'Đường trắng' => 5];
        }
        if (str_contains($name, 'espresso')) {
            return ['Cà phê hạt' => 20];
        }
        if (str_contains($name, 'americano')) {
            return ['Cà phê hạt' => 18];
        }
        if (str_contains($name, 'cappuccino')) {
            return ['Cà phê hạt' => 18, 'Sữa tươi' => 120, 'Whipping cream' => 20, 'Đường trắng' => 8];
        }
        if (str_contains($name, 'latte')) {
            return ['Cà phê hạt' => 18, 'Sữa tươi' => 150, 'Đường trắng' => 8];
        }
        if (str_contains($name, 'mocha')) {
            return ['Cà phê hạt' => 18, 'Sữa tươi' => 120, 'Siro caramel' => 15, 'Đường trắng' => 8];
        }
        if (str_contains($name, 'macchiato')) {
            return ['Cà phê hạt' => 18, 'Sữa tươi' => 80, 'Kem béo' => 20, 'Siro vani' => 10];
        }
        if (str_contains($name, 'trà xanh sữa')) {
            return ['Trà xanh' => 15, 'Bột sữa' => 25, 'Đường trắng' => 20, 'Kem béo' => 15];
        }
        if (str_contains($name, 'trà đen macchiato')) {
            return ['Trà đen' => 15, 'Đường trắng' => 20, 'Kem béo' => 30, 'Whipping cream' => 20, 'Sữa tươi' => 20];
        }
        if (str_contains($name, 'matcha latte')) {
            return ['Bột matcha' => 10, 'Sữa tươi' => 120, 'Đường trắng' => 15];
        }
        if (str_contains($name, 'trà đào cam sả')) {
            return ['Trà đen' => 10, 'Siro đào' => 20, 'Đường trắng' => 15, 'Đào ngâm' => 30, 'Cam tươi' => 1];
        }
        if (str_contains($name, 'trà dâu tươi')) {
            return ['Trà đen' => 10, 'Siro dâu' => 15, 'Dâu tây' => 50, 'Đường trắng' => 20];
        }
        if (str_contains($name, 'sữa chua đá xay')) {
            return ['Sữa tươi' => 50, 'Sữa đặc' => 30, 'Đường trắng' => 10];
        }
        if (str_contains($name, 'nước chanh')) {
            return ['Chanh tươi' => 2, 'Đường trắng' => 20];
        }
        if (str_contains($name, 'nước ép dâu tây')) {
            return ['Dâu tây' => 150, 'Đường trắng' => 20];
        }
        if (str_contains($name, 'nước cam')) {
            return ['Cam tươi' => 2, 'Đường trắng' => 15];
        }
        if (str_contains($name, 'sinh tố xoài')) {
            return ['Xoài' => 120, 'Sữa tươi' => 80, 'Đường trắng' => 15];
        }
        if (str_contains($name, 'sinh tố bơ')) {
            return ['Bơ' => 120, 'Sữa tươi' => 100, 'Đường trắng' => 15];
        }
        if (str_contains($name, 'bánh flan')) {
            return ['Bánh flan' => 1];
        }
        if (str_contains($name, 'pudding trứng')) {
            return ['Pudding trứng' => 1];
        }

        return [];
    }
}
