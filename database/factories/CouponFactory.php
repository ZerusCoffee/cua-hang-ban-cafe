<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['percentage', 'fixed', 'free_shipping']);

        return [
            'code'                     => strtoupper($this->faker->unique()->bothify('????####')),
            'name'                     => $this->faker->randomElement([
                'Chào mừng khách hàng mới',
                'Ưu đãi cuối tuần',
                'Flash sale 12.12',
                'Giảm giá sinh nhật',
                'Khách hàng thân thiết',
            ]),
            'description'              => $this->faker->randomElement([
                'Áp dụng cho tất cả sản phẩm trong cửa hàng.',
                'Không áp dụng cùng các chương trình khuyến mãi khác.',
                'Chỉ áp dụng cho đơn hàng đầu tiên.',
                'Áp dụng cho đơn hàng từ ' . number_format($this->faker->randomElement([100000, 200000, 500000]), 0, ',', '.') . 'đ trở lên.',
                'Ưu đãi có thời hạn, áp dụng trong khi còn mã.',
            ]),
            'type'                     => $type,
            'value'                    => match ($type) {
                'percentage'    => $this->faker->randomElement([5, 10, 15, 20, 30, 50]),
                'fixed'         => $this->faker->randomElement([10000, 20000, 50000, 100000, 200000]),
                'free_shipping' => 0,
            },
            'minimum_order_amount'     => $this->faker->randomElement([0, 100000, 200000, 500000]),
            'maximum_discount_amount'  => $type === 'percentage'
                ? $this->faker->randomElement([50000, 100000, 200000, null])
                : null,
            'usage_limit'              => $this->faker->randomElement([null, 50, 100, 200, 500]),
            'used_count'               => 0,
            'usage_limit_per_customer' => $this->faker->randomElement([null, 1, 2, 3]),
            'starts_at'                => now()->subDays(rand(0, 30)),
            'expires_at'               => $this->faker->randomElement([
                null,
                now()->addDays(rand(7, 90)),
            ]),
            'is_active'                => true,
        ];
    }

    // ─── States ───────────────────────────────────────────────────────────────

    public function percentage(): static
    {
        return $this->state([
            'type'  => 'percentage',
            'value' => $this->faker->randomElement([10, 20, 30, 50]),
        ]);
    }

    public function fixed(): static
    {
        return $this->state([
            'type'  => 'fixed',
            'value' => $this->faker->randomElement([50000, 100000, 200000]),
        ]);
    }

    public function freeShipping(): static
    {
        return $this->state([
            'type'  => 'free_shipping',
            'value' => 0,
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'starts_at'  => now()->subDays(60),
            'expires_at' => now()->subDays(rand(1, 30)),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function noLimit(): static
    {
        return $this->state([
            'usage_limit'              => null,
            'usage_limit_per_customer' => null,
        ]);
    }
}
