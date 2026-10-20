<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'supplier_id',
        'batch_number',
        'mfg_date',
        'expiry_date',
        'purchase_cost',
        'selling_price',
        'stock_quantity',
    ];

    protected $casts = [
        'mfg_date' => 'date',
        'expiry_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'stock_quantity' => 'integer',
    ];

    protected $appends = ['expiry_status', 'days_to_expiry'];

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'batch_id');
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class, 'batch_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('stock_quantity', '>', 0)
                     ->where('expiry_date', '>', now()->toDateString());
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<=', now()->toDateString());
    }

    public function scopeCriticalExpiry($query, $days = 30)
    {
        $today = now()->toDateString();
        $target = now()->addDays($days)->toDateString();

        return $query->where('expiry_date', '>', $today)
                     ->where('expiry_date', '<=', $target);
    }

    public function scopeNearExpiry($query, $days = 90)
    {
        $critical = now()->addDays(30)->toDateString();
        $target = now()->addDays($days)->toDateString();

        return $query->where('expiry_date', '>', $critical)
                     ->where('expiry_date', '<=', $target);
    }

    public function getDaysToExpiryAttribute(): int
    {
        if (!$this->expiry_date) {
            return 0;
        }
        return (int) now()->diffInDays(Carbon::parse($this->expiry_date), false);
    }

    public function getExpiryStatusAttribute(): array
    {
        $days = $this->days_to_expiry;

        if ($days <= 0) {
            return [
                'status' => 'Expired',
                'class' => 'bg-red-100 text-red-800 border-red-300',
                'color' => 'red',
                'badge' => 'EXPIRED'
            ];
        }

        if ($days <= 30) {
            return [
                'status' => 'Critical (<= 30d)',
                'class' => 'bg-orange-100 text-orange-800 border-orange-300',
                'color' => 'orange',
                'badge' => $days . 'd left'
            ];
        }

        if ($days <= 90) {
            return [
                'status' => 'Near Expiry (<= 90d)',
                'class' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                'color' => 'yellow',
                'badge' => $days . 'd left'
            ];
        }

        return [
            'status' => 'Good',
            'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'color' => 'emerald',
            'badge' => $days . 'd left'
        ];
    }
}
