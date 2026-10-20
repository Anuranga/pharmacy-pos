<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'amount_received',
        'payment_method',
        'notes',
        'received_by',
        'date',
    ];

    protected $casts = [
        'amount_received' => 'decimal:2',
        'date' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
