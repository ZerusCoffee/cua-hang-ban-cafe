<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
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
        $banhCategory = Category::where('slug', 'banh')->first();

        // ==================== CÀ PHÊ ====================
        $coffees = [
            'Cà phê đen nóng' => 'cafe_den_nong_3.png',   // hoặc cafe_den_nong_0.png, cafe_den_nong_1.png, cafe_den_nong_2.png
            'Cà phê đen đá' => 'cafe_den_da_1.png',
            'Cà phê sữa nóng' => 'cafe_sua_nong_0.jpeg',
            'Cà phê sữa đá' => 'cafe_sua_da_1.png',
            'Cappuccino nóng' => 'cappucino_nong_0.png',
            'Cappuccino đá' => 'cappucino_da_0.png',
            'Latte nóng' => 'latte_nong_0.png',
            'Latte đá' => 'latta_da_0.png',
            'Espresso nóng' => 'espresso_nong_2.png',
            'Espresso đá' => 'espresso_da_0.png',
            'Americano nóng' => 'americano_nong.png',
            'Americano đá' => 'americano_da.png',
            'Mocha nóng' => 'mocha_nong.png',
            'Mocha đá' => 'mocha_da.jpg',
            'Macchiato nóng' => 'machiato_nong.png',
            'Macchiato đá' => 'machiato_da.png',
        ];

        foreach ($coffees as $name => $image) {
            $this->createProduct($coffeeCategory->id, $name, 'Đồ uống cà phê thơm ngon',
                'Một thức uống với hương vị đậm đà và đặc trưng của cà phê nguyên chất.', $image);
        }

        // ==================== ĐỒ UỐNG ====================
        $beverages = [
            'Trà đen Macchiato' => 'tra_den_macchiato_0.jpg',
            'Trà xanh sữa' => 'tra_xanh_sua_0.png',
            'Matcha Latte' => 'matcha_latte_0.png',
            'Trà đào cam sả' => 'tra_dao_cam_sa_0.png',
            'Trà dâu tươi' => 'tra_dau_tuoi_0.webp',
            'Sữa chua đá xay' => 'sua_chua_da_xay_0.png',
            'Sinh tố xoài' => 'st_xoai_0.webp',
            'Sinh tố bơ' => 'st_bo_0.jpg',
            'Nước ép dâu tây' => 'st_dau_0.jpg',
        ];

        foreach ($beverages as $name => $image) {
            $this->createProduct($beverageCategory->id, $name, 'Đồ uống tươi mát',
                'Được pha chế từ nguyên liệu tươi ngon nhất, giữ nguyên hương vị tự nhiên.', $image);
        }

        // ==================== SNACK ====================
        $snacks = [
            'Hạt hướng dương' => 'hat_huong_duong_0.jpg',
        ];

        foreach ($snacks as $name => $image) {
            if ($snackCategory) {
                $this->createProduct($snackCategory->id, $name, 'Đồ ăn vặt ngọt ngào',
                    'Ăn Ăn Ăn.', $image);
            }
        }

        // ==================== BÁNH ====================
        $cakes = [
            'Bánh flan' => 'flan_0.jpg',
            'Pudding trứng' => 'pudding_trung_0.png',
            'Bánh quy bơ' => 'banh_quy_bo_1.png',
            'Bánh quy socola' => 'banh_quy_socola_0.jpg',
        ];
        foreach ($cakes as $name => $image) {
            if ($banhCategory) {
                $this->createProduct($banhCategory->id, $name, 'Bánh ngọt ngon',
                    'Món ăn kèm hoàn hảo cho ly nước của bạn thêm phần trọn vẹn.', $image);
            }
        }

    }

        private function createProduct(
        int $categoryId,
        string $name,
        string $shortDesc,
        string $desc,
        string $primaryImageName
    ): void
    {
        $product = Product::create([
            'category_id'       => $categoryId,
            'name'              => $name,
            'slug'              => Str::slug($name),
            'recommended_price' => 0,
            'profit_rate'       => 0,
            'short_description' => $shortDesc,
            'description'       => $desc,
            'is_featured'       => (bool)rand(0, 1),
            'is_active'         => true,
            'view_count'        => rand(10, 500)
        ]);

        // Tạo Recipe
        $recipe = $this->getRecipeByProductName($name);
        foreach ($recipe as $ingredientName => $amount) {
            $ingredient = Ingredient::where('name', $ingredientName)->first();
            if ($ingredient) {
                RecipeDetail::create([
                    'product_id'    => $product->id,
                    'ingredient_id' => $ingredient->id,
                    'amount'        => $amount,
                ]);
            }
        }

        // Tính giá bán
        $costPrice = $product->cost_price ?? 0;
        $profitRate = rand(30, 70);

        if ($costPrice > 0) {
            $calculatedPrice = $costPrice * (1 + $profitRate / 100);
            $recommendedPrice = ceil($calculatedPrice / 1000) * 1000;
            $realProfitRate = round((($recommendedPrice - $costPrice) / $costPrice) * 100, 2);
        } else {
            $recommendedPrice = rand(25, 65) * 1000;
            $realProfitRate = $profitRate;
        }

        $product->update([
            'recommended_price' => $recommendedPrice,
            'profit_rate'       => $realProfitRate,
        ]);

        // Gắn ảnh
        $this->attachSpecificImages($product, $primaryImageName);
    }

    private function getRecipeByProductName(string $productName): array
    {
        $recipes = [
            // ==================== CÀ PHÊ ====================
            'Cà phê đen nóng' => [
                'Cà phê hạt' => 18,
                'Đường trắng' => 10,
            ],
            'Cà phê đen đá' => [
                'Cà phê hạt' => 18,
                'Đường trắng' => 10,
            ],
            'Cà phê sữa nóng' => [
                'Cà phê hạt' => 18,
                'Sữa đặc' => 25,
                'Đường trắng' => 5,
            ],
            'Cà phê sữa đá' => [
                'Cà phê hạt' => 18,
                'Sữa đặc' => 25,
                'Đường trắng' => 5,
            ],

            'Cappuccino nóng' => [
                'Cà phê hạt' => 18,
                'Sữa tươi' => 120,
                'Whipping cream' => 20,
                'Đường trắng' => 8,
            ],
            'Cappuccino đá' => [
                'Cà phê hạt' => 18,
                'Sữa tươi' => 120,
                'Whipping cream' => 20,
                'Đường trắng' => 8,
            ],

            'Latte nóng' => [
                'Cà phê hạt' => 18,
                'Sữa tươi' => 150,
                'Đường trắng' => 8,
            ],
            'Latte đá' => [
                'Cà phê hạt' => 18,
                'Sữa tươi' => 150,
                'Đường trắng' => 8,
            ],

            'Espresso nóng' => [
                'Cà phê hạt' => 20,
            ],
            'Espresso đá' => [
                'Cà phê hạt' => 20,
            ],

            'Americano nóng' => [
                'Cà phê hạt' => 18,
            ],
            'Americano đá' => [
                'Cà phê hạt' => 18,
            ],

            'Mocha nóng' => [
                'Cà phê hạt' => 18,
                'Sữa tươi' => 120,
                'Siro caramel' => 15,
                'Đường trắng' => 8,
            ],
            'Mocha đá' => [
                'Cà phê hạt' => 18,
                'Sữa tươi' => 120,
                'Siro caramel' => 15,
                'Đường trắng' => 8,
            ],

            'Macchiato nóng' => [
                'Cà phê hạt' => 18,
                'Sữa tươi' => 80,
                'Kem béo' => 20,
                'Siro vani' => 10,
            ],
            'Macchiato đá' => [
                'Cà phê hạt' => 18,
                'Sữa tươi' => 80,
                'Kem béo' => 20,
                'Siro vani' => 10,
            ],

            // ==================== ĐỒ UỐNG KHÁC ====================
            'Trà đen Macchiato' => [
                'Trà đen' => 15,
                'Đường trắng' => 20,
                'Kem béo' => 30,
                'Whipping cream' => 20,
                'Sữa tươi' => 20,
            ],

            'Trà xanh sữa' => [
                'Trà xanh' => 15,
                'Bột sữa' => 25,
                'Đường trắng' => 20,
                'Kem béo' => 15,
            ],

            'Matcha Latte' => [
                'Bột matcha' => 10,
                'Sữa tươi' => 120,
                'Đường trắng' => 15,
            ],

            'Trà đào cam sả' => [
                'Trà đen' => 10,
                'Siro đào' => 20,
                'Đường trắng' => 15,
                'Đào ngâm' => 30,
                'Cam tươi' => 1,
            ],

            'Trà dâu tươi' => [
                'Trà đen' => 10,
                'Siro dâu' => 15,
                'Dâu tây' => 50,
                'Đường trắng' => 20,
            ],

            'Sữa chua đá xay' => [
                'Sữa tươi' => 50,
                'Sữa đặc' => 30,
                'Đường trắng' => 10,
            ],

            'Sinh tố xoài' => [
                'Xoài' => 120,
                'Sữa tươi' => 80,
                'Đường trắng' => 15,
            ],

            'Sinh tố bơ' => [
                'Bơ' => 120,
                'Sữa tươi' => 100,
                'Đường trắng' => 15,
            ],

            'Nước ép dâu tây' => [
                'Dâu tây' => 150,
                'Đường trắng' => 20,
            ],

            // ==================== SNACK & BÁNH ====================
            'Bánh flan' => [
                'Bánh flan' => 1,
            ],

            'Pudding trứng' => [
                'Pudding trứng' => 1,
            ],
        ];

        // Trả về công thức nếu tìm thấy, ngược lại trả về mảng rỗng
        return $recipes[$productName] ?? [];
    }

    /**
     * Gắn ảnh cho sản phẩm - Dành cho model ProductImage (image_path)
     */
    private
    function attachSpecificImages(Product $product, string $primaryImageName): void
    {
        $basePath = 'products/';

        // Ảnh chính
        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => $basePath . $primaryImageName,
            'alt_text' => $product->name . ' - Ảnh chính',
            'is_primary' => false,   // để booted() tự xử lý
        ]);

        // Một số sản phẩm có thể thêm ảnh phụ (tùy chọn)
        $extraImages = $this->getExtraImages($product->name);

        foreach ($extraImages as $index => $imageName) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $basePath . $imageName,
                'alt_text' => $product->name . ' - Ảnh ' . ($index + 2),
                'is_primary' => false,
            ]);
        }
    }

    /**
     * Danh sách ảnh phụ cho một số sản phẩm (có thể mở rộng)
     */
    private
    function getExtraImages(string $productName): array
    {
        $name = mb_strtolower($productName);

        if (str_contains($name, 'cà phê sữa')) {
            return ['cafe_sua_da_1.png', 'cafe_sua_nong_0.jpeg'];
        }

        if (str_contains($name, 'cappuccino')) {
            return ['cappucino_da_0.png'];
        }

        if (str_contains($name, 'latte')) {
            return ['latta_da_0.png'];
        }

        if (str_contains($name, 'mocha')) {
            return ['mocha_da.jpg'];
        }

        // Mặc định không có ảnh phụ
        return [];
    }
}
