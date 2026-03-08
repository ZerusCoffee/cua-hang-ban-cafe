<?php
namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => $this->faker->unique()->words(3, true),
            'recommended_price' => $this->faker->numberBetween(10000, 500000),
            'profit_rate' => $this->faker->numberBetween(20, 80),
            'short_description' => $this->faker->sentence(10),
            'description' => $this->faker->paragraph(5),
            'is_featured' => $this->faker->boolean(30), // 30%
            'is_active' => $this->faker->boolean(80),   // 80%
            'view_count' => $this->faker->numberBetween(0, 1000),
        ];
    }

    /**
     * State for featured products
     */
    public function featured(): static
    {
        return $this->state(function () {
            return [
                'is_featured' => true,
            ];
        });
    }

    /**
     * State for inactive products
     */
    public function inactive(): static
    {
        return $this->state(function () {
            return [
                'is_active' => false,
            ];
        });
    }

    /**
     * State for coffee products
     */
    public function coffee(): static
{
    return $this->state(function () {

        $name = $this->faker->unique()->randomElement([
            'Cà phê đen nóng',
            'Cà phê đen đá',
            'Cà phê sữa nóng',
            'Cà phê sữa đá',
            'Cappuccino nóng',
            'Cappuccino đá',
            'Latte nóng',
            'Latte đá',
            'Espresso nóng',
            'Espresso đá',
            'Americano nóng',
            'Americano đá',
            'Mocha nóng',
            'Mocha đá',
            'Macchiato nóng',
            'Macchiato đá',
        ]);

        return [
            'name' => $name,
            'short_description' => 'Đồ uống cà phê thơm ngon',
            'recommended_price' => $this->faker->numberBetween(20000, 60000),
            'profit_rate' => $this->faker->numberBetween(30, 70),
        ];
    });
}

    /**
     * State for beverage products
     */
    public function beverage(): static
    {
        return $this->state(function () {

            $name = $this->faker->unique()->randomElement([
                'Trà sữa nóng',
                'Trà sữa đá',
                'Nước chanh nóng',
                'Nước chanh đá',
                'Nước cam nóng',
                'Nước cam đá',
                'Sinh tố xoài',
                'Sinh tố bơ',
                'Nước dừa',
                'Smoothie dâu',
                'Smoothie việt quất',
            ]);

            return [
                'name' => $name,
                'short_description' => 'Đồ uống tươi mát',
                'recommended_price' => $this->faker->numberBetween(15000, 50000),
                'profit_rate' => $this->faker->numberBetween(35, 75),
            ];
        });
    }

    public function snack(): static
    {
        return $this->state(function () {

            $name = $this->faker->unique()->randomElement([
                'Khoai tây lắc phô mai',
                'Khoai tây lắc sữa',
                'Bánh tráng trộn',
                'Bánh tráng muối',
                'Bánh tráng me',
                'Bánh tráng sa tế',
                'Bò khô',
                'Khô gà lá chanh',
                'Khô bò',
                'Hạt dẻ cười rang muối',
                'Hạt điều rang muối',
                'Hạnh nhân nướng mật ong',
                'Bánh quy bơ',
                'Bánh quy socola',
                'Bánh quế',
                'Snack rong biển',
                'Snack ngô',
                'Bim bim khoai tây',
                'Mực khô',
                'Chà bông',
            ]);

            return [
                'name' => $name,
                'short_description' => 'Đồ ăn vặt giòn ngon',
                'recommended_price' => $this->faker->numberBetween(10000, 35000),
                'profit_rate' => $this->faker->numberBetween(40, 70),
            ];
        });
    }
}
