<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        "code",
        "name",
        "description",
        "type",
        "value",
        "minimum_order_amount",
        "maximum_discount_amount",
        "usage_limit",
        "usage_limit_per_customer",
        "starts_at",
        "expires_at",
        "is_active",
    ];

    protected $casts = [
        "value" => "decimal:2",
        "minimum_order_amount" => "decimal:2",
        "maximum_discount_amount" => "decimal:2",
        "usage_limit" => "integer",
        "usage_limit_per_customer" => "integer",
        "starts_at" => "datetime",
        "expires_at" => "datetime",
        "is_active" => "boolean",
    ];

    #[Scope]
    protected function active(Builder $builder)
    {
        return $builder->where("is_active", true);
    }

    //check valid coupon
    protected function valid(Builder $builder)
    {
        $builder
            ->where("is_active", true)
            ->where(function ($query) {
                $query
                    ->whereNull("starts_at")
                    ->orWhere("starts_at", "<=", now()); // Bắt đầu trước hoặc vào hiện tại
            })
            ->where(function ($query) {
                $query
                    ->whereNull("expires_at")
                    ->orWhere("expires_at", ">=", now()); // Kết thúc sau hoặc vào hiện tại
            });
    }

    public function orders()
    {
        return $this->hasMany(Order::class); // 1 Mã giảm giá có nhiều Đơn hàng
    }

    public function usages()
    {
        return $this->hasMany(CouponUsage::class); // 1 Mã giảm giá có nhiều Lịch sử sử dụng
    }

    public function isValid()
    {
        if (!$this->is_active) {
            return false;
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }
        if (
            $this->usage_limit &&
            $this->usages()->count() >= $this->usage_limit
        ) {
            return false;
        }
        return true;
    }

    public function canBeUsedByCustomer($customerId)
    {
        if (!$this->isValid()) {
            return false;
        }
        if ($this->usage_limit_per_customer) {
            $usageCount = $this->usages()
                ->where("customer_id", $customerId)
                ->count(); // Đếm số lần sử dụng của khách hàng
            if ($usageCount >= $this->usage_limit_per_customer) {
                return false;
            }
        }
        return true;
    }

    public function calculateDiscount($subtotal)
    {
        if (
            $this->minimum_order_amount &&
            $subtotal < $this->minimum_order_amount
        ) {
            return 0;
        }
        if ($this->type === "fixed") {
            $discount = $this->value;
        } elseif ($this->type === "percentage") {
            $discount = ($this->value / 100) * $subtotal; // Tính phần trăm giảm giá
            if (
                $this->maximum_discount_amount &&
                $discount > $this->maximum_discount_amount
            ) {
                // Giới hạn giảm giá tối đa
                $discount = $this->maximum_discount_amount;
            }
        } else {
            $discount = 0;
        }
        return min($discount, $subtotal); // Giảm giá không vượt quá tổng phụ
    }
}
