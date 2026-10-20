<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'credit_period_days',
        'current_balance',
    ];

    protected $casts = [
        'credit_period_days' => 'integer',
        'current_balance' => 'decimal:2',
    ];

    public function grns()
    {
        return $this->hasMany(Grn::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function batches()
    {
        return $this->hasMany(ProductBatch::class);
    }
}
