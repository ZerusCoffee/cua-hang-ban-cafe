<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_id',
        'coupon_id',
        'subtotal',
        'discount_amount',
        'shipping_fee',
        'tax_amount',
        'total',
        'shipping_full_name',
        'shipping_phone',
        'shipping_address_details',
        'shipping_ward',
        'shipping_province',
        'payment_method',
        'payment_status',
        'status',
        'transaction_id',
        'tracking_number',
        'customer_notes',
        'admin_notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
    ];

    // Lọc đơn hàng theo trạng thái
    #[Scope()]
    protected function ofStatus(Builder $builder, string $status){
        return $builder->where('status', $status);
    }

    // Lọc đơn hàng theo trạng thái thanh toán
    #[Scope()]
    protected function paymentStatus(Builder $builder, string $paymentStatus){
        return $builder->where('payment_status', $paymentStatus);
    }

    //Lọc đơn hàng đang chờ
    #[Scope()]
    protected function pending(Builder $builder){
        return $builder->where('status', 'pending');
    }

    //Lọc đơn hàng đã xác nhận
    #[Scope()]
    protected function confirmed(Builder $builder){
        return $builder->where('status', 'confirmed');
    }

    //Lọc đơn hàng đang giao
    #[Scope()]
    protected function shipped(Builder $builder){
        return $builder->where('status', 'shipped');
    }

    //Lọc đơn hàng đã hủy
    #[Scope()]
    protected function cancelled(Builder $builder){
        return $builder->where('status', 'cancelled');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at', 'desc');
    }


    public function getFullAddressAttribute()
    {
        return implode(', ', [
            $this->shipping_address_details,
            $this->shipping_ward,
            $this->shipping_province
        ]);
    }

    public function updateStatus($newStatus, $note = null, $userId = null){
        $this->update(['status' => $newStatus]);

        // Ghi lại lịch sử thay đổi trạng thái
        $this->statusHistories()->create([
            'status' => $newStatus,
            'notes' => $note,
            'user_id' => $userId,
        ]);
    }


     protected static function boot(){
        parent::boot();

        static::creating(function($order){
            if (empty($order->order_number)){
                $order->order_number = 'ORD-' . strtoupper(uniqid());
            }
        });

        static::created(function($order){
            // Tạo bản ghi lịch sử trạng thái khi đơn hàng được tạo
            $order->statusHistories()->create([
                'status' => $order->status,
                'notes' => 'Order created',
            ]);
            // Có thể thêm logic gửi email thông báo đơn hàng mới ở đây
        });

        static::deleting(function($order){
            // Xóa các mục trong đơn hàng khi đơn hàng bị xóa
            $order->items()->delete();
            // Xóa lịch sử trạng thái đơn hàng khi đơn hàng bị xóa
            $order->statusHistories()->delete();
        });
    }

}
