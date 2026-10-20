<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'cashier_id',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'payment_type',
        'amount_paid',
        'balance_due',
        'status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function prescription()
    {
        return $this->hasOne(Prescription::class);
    }

    public function getTotalCogsAttribute(): float
    {
        return (float) $this->items->sum(function ($item) {
            return $item->quantity * $item->cost_price;
        });
    }

    public function getGrossProfitAttribute(): float
    {
        return (float) $this->grand_total - $this->total_cogs;
    }
}
