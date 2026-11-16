<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_number',
        'grn_id',
        'supplier_id',
        'processed_by',
        'total_return_amount',
        'settlement_method',
        'reason',
        'notes',
    ];

    protected $casts = [
        'total_return_amount' => 'decimal:2',
    ];

    public function grn()
    {
        return $this->belongsTo(Grn::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}
