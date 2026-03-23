<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        // ── Coupon cố định cho dev/test ───────────────────────────────────────

        $fixed = [
            [
                'code'                     => 'WELCOME10',
                'name'                     => 'Chào mừng khách hàng mới',
                'description'              => 'Giảm 10% cho đơn hàng đầu tiên',
                'type'                     => 'percentage',
                'value'                    => 10,
                'minimum_order_amount'     => 0,
                'maximum_discount_amount'  => 100000,
                'usage_limit'              => null,
                'used_count'               => 0,
                'usage_limit_per_customer' => 1,
                'starts_at'                => now(),
                'expires_at'               => now()->addYear(),
                'is_active'                => true,
            ],
            [
                'code'                     => 'SALE50K',
                'name'                     => 'Giảm 50.000đ',
                'description'              => 'Giảm cố định 50.000đ cho đơn từ 200.000đ',
                'type'                     => 'fixed',
                'value'                    => 50000,
                'minimum_order_amount'     => 200000,
                'maximum_discount_amount'  => null,
                'usage_limit'              => 100,
                'used_count'               => 0,
                'usage_limit_per_customer' => 2,
                'starts_at'                => now(),
                'expires_at'               => now()->addMonths(3),
                'is_active'                => true,
            ],
            [
                'code'                     => 'FREESHIP',
                'name'                     => 'Miễn phí vận chuyển',
                'description'              => 'Miễn phí vận chuyển cho mọi đơn hàng',
                'type'                     => 'free_shipping',
                'value'                    => 0,
                'minimum_order_amount'     => 0,
                'maximum_discount_amount'  => null,
                'usage_limit'              => null,
                'used_count'               => 0,
                'usage_limit_per_customer' => null,
                'starts_at'                => now(),
                'expires_at'               => now()->addMonths(1),
                'is_active'                => true,
            ],
            [
                'code'                     => 'EXPIRED',
                'name'                     => 'Coupon hết hạn (test)',
                'description'              => 'Dùng để test trường hợp coupon hết hạn',
                'type'                     => 'fixed',
                'value'                    => 100000,
                'minimum_order_amount'     => 0,
                'maximum_discount_amount'  => null,
                'usage_limit'              => null,
                'used_count'               => 0,
                'usage_limit_per_customer' => null,
                'starts_at'                => now()->subMonths(2),
                'expires_at'               => now()->subDay(),
                'is_active'                => true,
            ],
            [
                'code'                     => 'INACTIVE',
                'name'                     => 'Coupon bị tắt (test)',
                'description'              => 'Dùng để test trường hợp coupon inactive',
                'type'                     => 'percentage',
                'value'                    => 20,
                'minimum_order_amount'     => 0,
                'maximum_discount_amount'  => null,
                'usage_limit'              => null,
                'used_count'               => 0,
                'usage_limit_per_customer' => null,
                'starts_at'                => now(),
                'expires_at'               => now()->addYear(),
                'is_active'                => false,
            ],
        ];

        foreach ($fixed as $coupon) {
            Coupon::updateOrCreate(['code' => $coupon['code']], $coupon);
        }

        // ── Random coupons cho môi trường dev ─────────────────────────────────

        if (app()->isLocal()) {
            Coupon::factory()->count(5)->percentage()->create();
            Coupon::factory()->count(5)->fixed()->create();
            Coupon::factory()->count(3)->freeShipping()->create();
            Coupon::factory()->count(3)->expired()->create();
            Coupon::factory()->count(2)->inactive()->create();
        }
    }
}
