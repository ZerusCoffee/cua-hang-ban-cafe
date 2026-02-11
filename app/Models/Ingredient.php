<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingredient extends Model
{
    /** @use HasFactory<\Database\Factories\IngredientFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'unit_id',
        'price',
        'stock',
        'threshold'
    ];

    protected static function booted()
    {
        static::created(function ($ingredient) {

            logger('CREATED EVENT TRIGGERED');

            $ingredient->sku = 'NL' . str_pad($ingredient->id, 5, '0', STR_PAD_LEFT);

            $ingredient->saveQuietly();
        });
    }

    public function scopeSearch($query, $keyword)
    {
        return $query->where('name', 'like', "%$keyword%");
    }

    public function scopeUnit($query, $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<', 'threshold');
    }
}
