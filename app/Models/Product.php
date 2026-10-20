<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'barcode_sku',
        'brand_name',
        'generic_name',
        'dosage_strength',
        'item_type',
        'category_id',
        'base_uom',
        'units_per_pack',
        'reorder_min',
        'reorder_max',
        'requires_prescription',
        'is_controlled',
        'unit_cost_price',
        'unit_retail_price',
    ];

    protected $casts = [
        'requires_prescription' => 'boolean',
        'is_controlled' => 'boolean',
        'unit_cost_price' => 'decimal:2',
        'unit_retail_price' => 'decimal:2',
        'units_per_pack' => 'integer',
        'reorder_min' => 'integer',
        'reorder_max' => 'integer',
    ];

    protected $appends = ['total_stock'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function batches()
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function activeBatches()
    {
        return $this->hasMany(ProductBatch::class)
            ->where('stock_quantity', '>', 0)
            ->where('expiry_date', '>', now()->toDateString())
            ->orderBy('expiry_date', 'asc');
    }

    public function grnItems()
    {
        return $this->hasMany(GrnItem::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function getTotalStockAttribute(): int
    {
        return (int) $this->batches()->sum('stock_quantity');
    }

    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('barcode_sku', 'like', "%{$term}%")
              ->orWhere('brand_name', 'like', "%{$term}%")
              ->orWhere('generic_name', 'like', "%{$term}%");
        });
    }

    public function scopeLowStock($query)
    {
        return $query->whereHas('batches', function ($q) {
            // has batches
        })->withSum('batches as total_stock_count', 'stock_quantity')
          ->havingRaw('COALESCE(total_stock_count, 0) <= reorder_min');
    }
}
