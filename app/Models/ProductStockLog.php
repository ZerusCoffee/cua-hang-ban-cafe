<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStockLog extends Model
{
    protected $fillable = [
        'product_id',
        'max_quantity',
        'logged_at'
    ];

    protected $casts = [
        'logged_at' => 'datetime',
    ];

    protected function product(): BelongsTo
    {
      return $this->belongsTo(Product::class);
    }

    public static function calculateFor(Product $product): int
    {
        $recipeDetails = $product->recipeDetails()->with('ingredient')->get();

        if ($recipeDetails->isEmpty()) {
            return 0;
        }

        $min = null;

        foreach ($recipeDetails as $detail) {
            $ingredient = $detail->ingredient;

            if (!$ingredient || $detail->amount <= 0) {
                return 0;
            }

            $possible = (int) floor($ingredient->stock / $detail->amount);

            if ($min === null || $possible < $min) {
                $min = $possible;
            }
        }

        return $min ?? 0;
    }

    /**
     * Tạo 1 dòng log mới tại thời điểm hiện tại
     */
    public static function snapshot(Product $product): self
    {
        return self::create([
            'product_id'   => $product->id,
            'max_quantity' => self::calculateFor($product),
            'logged_at'    => now(),
        ]);
    }
}
