<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingredient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'unit_id',
        'cost_price',
        'stock',
        'threshold'
    ];

    protected $attributes = [
        'cost_price' => 0,
        'stock' => 0,
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function importOrderDetails(): HasMany
    {
        return $this->hasMany(ImportOrderDetail::class);
    }

    public function recipeDetails(): HasMany
    {
        return $this->hasMany(RecipeDetail::class);
    }

    public function importLogs(): HasMany
    {
        return $this->hasMany(IngredientImportLog::class);
    }


    protected static function booted()
    {
        static::created(function ($ingredient) {

            logger('CREATED EVENT TRIGGERED');

            $ingredient->sku = 'NL' . str_pad($ingredient->id, 5, '0', STR_PAD_LEFT);

            $ingredient->saveQuietly();
        });
    }

    public function safeDelete(): void
    {
        $hasImport = $this->importOrderDetails()->exists();
        if ($hasImport) {
            $this->delete(); //softDelete
        } else {
            $this->forceDelete(); //delete
        }
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
