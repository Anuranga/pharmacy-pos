<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_id',
        'product_id',
        'batch_number',
        'expiry_date',
        'cost_price',
        'retail_price',
        'quantity_received',
        'free_bonus_qty',
        'subtotal',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'cost_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'quantity_received' => 'integer',
        'free_bonus_qty' => 'integer',
    ];

    public function grn()
    {
        return $this->belongsTo(Grn::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
