<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'minimum_order_amount',
        'maximum_discount_amount',
        'usage_limit',
        'used_count',
        'usage_limit_per_customer',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'maximum_discount_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->active()
            ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }


    /**
     * CouponSeeder còn hiệu lực không?
     */
    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) return false;

        return true;
    }

    /**
     * Khách hàng này đã dùng coupon bao nhiêu lần?
     */
    public function usageCountForCustomer(int $customerId): int
    {
        return $this->usages()->where('customer_id', $customerId)->count();
    }

    /**
     * Khách hàng này còn được dùng không?
     */
    public function canBeUsedByCustomer(int $customerId): bool
    {
        if (!$this->isValid()) return false;

        if ($this->usage_limit_per_customer) {
            return $this->usageCountForCustomer($customerId) < $this->usage_limit_per_customer;
        }

        return true;
    }

    /**
     * Tính số tiền giảm thực tế cho đơn hàng
     */
    public function calculateDiscount(float $orderAmount): float
    {
        if ($orderAmount < $this->minimum_order_amount) return 0;

        $discount = match ($this->type) {
            'percentage' => $orderAmount * ($this->value / 100),
            'fixed' => $this->value,
            'free_shipping' => 0, // xử lý riêng bên shipping
            default => 0,
        };

        if ($this->maximum_discount_amount) {
            $discount = min($discount, $this->maximum_discount_amount);
        }

        // Không giảm quá tổng đơn hàng
        return min($discount, $orderAmount);
    }

    /**
     * Label hiển thị loại coupon
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'percentage' => "Giảm {$this->value}%",
            'fixed' => 'Giảm ' . number_format($this->value, 0, ',', '.') . 'đ',
            'free_shipping' => 'Miễn phí vận chuyển',
            default => $this->type,
        };
    }
}
