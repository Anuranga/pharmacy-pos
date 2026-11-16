<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_return_id',
        'grn_item_id',
        'product_id',
        'batch_id',
        'batch_number',
        'return_uom',
        'units_per_pack',
        'packs_returned',
        'quantity_returned',
        'unit_cost',
        'subtotal',
    ];

    protected $casts = [
        'units_per_pack' => 'integer',
        'packs_returned' => 'integer',
        'quantity_returned' => 'integer',
        'unit_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function grnItem()
    {
        return $this->belongsTo(GrnItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function batch()
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }
}
