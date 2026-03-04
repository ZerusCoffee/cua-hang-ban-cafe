<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ImportOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'supplier_id',
        'status',
        'notes',
        'imported_at',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ImportOrder $order) {
            if (empty($order->code)) {
                $order->code = 'PN-TMP-' . time();
            }
        });

        static::created(function (ImportOrder $order) {
            if (str_starts_with($order->code, 'PN-TMP-')) {
                $order->updateQuietly([
                    'code' => 'PN-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                ]);
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(ImportOrderDetail::class);
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->details->sum(fn ($d) => $d->quantity * $d->unit_price);
    }

    public function complete(): void
    {
        if ($this->status === 'completed') {
            return;
        }

        DB::transaction(function () {

            $this->loadMissing('details.ingredient');

            foreach ($this->details as $detail) {

                $ingredient = $detail->ingredient;

                $currentStock = $ingredient->stock ?? 0;
                $currentCost = $ingredient->cost_price ?? 0;

                $newQuantity = $detail->quantity;
                $newCost = $detail->unit_price;

                // Tính giá bình quân
                if ($currentStock > 0) {
                    $averageCost = (
                            ($currentStock * $currentCost)
                            + ($newQuantity * $newCost)
                        ) / ($currentStock + $newQuantity);
                } else {
                    $averageCost = $newCost;
                }

                // Làm tròn tiền VNĐ
                $averageCost = round($averageCost);

                // Update nguyên liệu
                $ingredient->update([
                    'stock' => $currentStock + $newQuantity,
                    'cost_price' => $averageCost,
                ]);
            }

            $this->update([
                'status' => 'completed',
                'imported_at' => now(),
            ]);
        });
    }

}
