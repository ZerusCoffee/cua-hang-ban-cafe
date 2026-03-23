<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Support\Facades\DB;

class CouponService
{
    /**
     * Validate coupon có thể dùng được không
     * Trả về ['valid' => true/false, 'message' => '...', 'coupon' => Coupon|null]
     */
    public function validate(string $code, int $customerId, float $orderAmount): array
    {
        $coupon = Coupon::where('code', strtoupper($code))->first();

        if (!$coupon) {
            return $this->invalid('Mã giảm giá không tồn tại.');
        }

        if (!$coupon->is_active) {
            return $this->invalid('Mã giảm giá không còn hiệu lực.');
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return $this->invalid('Mã giảm giá chưa đến thời gian sử dụng.');
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return $this->invalid('Mã giảm giá đã hết hạn.');
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return $this->invalid('Mã giảm giá đã hết lượt sử dụng.');
        }

        if ($orderAmount < $coupon->minimum_order_amount) {
            $min = number_format($coupon->minimum_order_amount, 0, ',', '.');
            return $this->invalid("Đơn hàng tối thiểu {$min}đ để áp dụng mã này.");
        }

        if ($coupon->usage_limit_per_customer) {
            $used = CouponUsage::where('coupon_id', $coupon->id)
                ->where('customer_id', $customerId)
                ->count();

            if ($used >= $coupon->usage_limit_per_customer) {
                return $this->invalid('Bạn đã dùng hết lượt cho mã giảm giá này.');
            }
        }

        return [
            'valid'           => true,
            'message'         => 'Áp dụng mã giảm giá thành công.',
            'coupon'          => $coupon,
            'discount_amount' => $coupon->calculateDiscount($orderAmount),
        ];
    }

    /**
     * Apply coupon vào order — ghi usage + tăng used_count
     */
    public function apply(Coupon $coupon, int $customerId, int $orderId, float $discountAmount): CouponUsage
    {
        return DB::transaction(function () use ($coupon, $customerId, $orderId, $discountAmount) {
            $usage = CouponUsage::create([
                'coupon_id'       => $coupon->id,
                'customer_id'     => $customerId,
                'order_id'        => $orderId,
                'discount_amount' => $discountAmount,
            ]);

            $coupon->increment('used_count');

            return $usage;
        });
    }

    /**
     * Validate + Apply trong 1 bước
     */
    public function validateAndApply(string $code, int $customerId, int $orderId, float $orderAmount): array
    {
        $result = $this->validate($code, $customerId, $orderAmount);

        if (!$result['valid']) {
            return $result;
        }

        $usage = $this->apply($result['coupon'], $customerId, $orderId, $result['discount_amount']);

        return array_merge($result, ['usage' => $usage]);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function invalid(string $message): array
    {
        return ['valid' => false, 'message' => $message, 'coupon' => null];
    }
}
