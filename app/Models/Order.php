<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number', 'customer_id', 'coupon_id',
        'subtotal', 'discount_amount', 'shipping_fee', 'tax_amount', 'total',
        'shipping_full_name', 'shipping_phone', 'shipping_address_details',
        'shipping_ward', 'shipping_province',
        'payment_method', 'payment_status', 'status',
        'transaction_id', 'tracking_number',
        'customer_notes', 'admin_notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    #[Scope]
    protected function ofStatus(Builder $builder, string $status): Builder
    {
        return $builder->where('status', $status);
    }

    #[Scope]
    protected function paymentStatus(Builder $builder, string $paymentStatus): Builder
    {
        return $builder->where('payment_status', $paymentStatus);
    }

    #[Scope]
    protected function pending(Builder $builder): Builder
    {
        return $builder->where('status', 'pending');
    }

    #[Scope]
    protected function confirmed(Builder $builder): Builder
    {
        return $builder->where('status', 'confirmed');
    }

    #[Scope]
    protected function delivered(Builder $builder): Builder
    {
        return $builder->where('status', 'delivered');
    }

    #[Scope]
    protected function cancelled(Builder $builder): Builder
    {
        return $builder->where('status', 'cancelled');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at', 'desc');
    }

    public function profitLogs(): HasManyThrough
    {
        return $this->hasManyThrough(OrderProfitLog::class, OrderItem::class);
    }

    public function getFullAddressAttribute(): string
    {
        return implode(', ', array_filter([
            $this->shipping_address_details,
            $this->shipping_ward,
            $this->shipping_province,
        ]));
    }

    public function updateStatus(string $newStatus, ?string $note = null, ?int $userId = null): void
    {
        $this->update(['status' => $newStatus]);

        if ($newStatus === 'delivered' && $this->payment_method === 'cod') {
            $this->update(['payment_status' => 'paid']);
        }

        $this->statusHistories()->create([
            'status' => $newStatus,
            'notes' => $note,
            'user_id' => $userId,
        ]);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(uniqid());
            }
        });

        static::created(function (Order $order) {
            $order->statusHistories()->create([
                'status' => $order->status,
                'notes' => 'Đơn hàng được tạo',
            ]);
        });

        static::deleting(function (Order $order) {
            $order->items()->delete();
            $order->statusHistories()->delete();
        });
    }
}
