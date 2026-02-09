<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        "customer_id",
        "full_name",
        "phone",
        "details",
        "ward",
        "province",
        "is_default"
    ];

    protected $casts = [
        "is_default" => "boolean"
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($address) {
            // Nếu là địa chỉ đầu tiên hoặc được đặt làm mặc định
            if ($address->is_default || !self::where('customer_id', $address->customer_id)->exists()) {
                // Bỏ mặc định của các địa chỉ khác
                self::where('customer_id', $address->customer_id)
                    ->update(['is_default' => false]);
                $address->is_default = true;
            }
        });

        static::updating(function ($address) {
            // Nếu đặt địa chỉ này làm mặc định
            if ($address->is_default) {
                // Bỏ mặc định của các địa chỉ khác
                self::where('customer_id', $address->customer_id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    // Lấy địa chỉ đầy đủ
    public function getFullAddressAttribute()
    {
        return "{$this->details}, {$this->ward}, {$this->province}";
    }

    public function belongsToCustomer($customerId)
    {
        return $this->customer_id == $customerId;
    }
}
