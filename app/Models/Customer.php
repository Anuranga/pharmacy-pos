<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'nic_number',
        'credit_limit',
        'current_debt_balance',
        'address',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'current_debt_balance' => 'decimal:2',
    ];

    protected $appends = ['remaining_credit'];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function creditSales()
    {
        return $this->hasMany(Sale::class)
            ->where('payment_type', 'credit_lending')
            ->where('balance_due', '>', 0);
    }

    public function settlements()
    {
        return $this->hasMany(CustomerSettlement::class);
    }

    public function extraPayments()
    {
        return $this->hasMany(CustomerExtraPayment::class);
    }

    public function getRemainingCreditAttribute(): float
    {
        $rem = (float) $this->credit_limit - (float) $this->current_debt_balance;
        return max(0.0, round($rem, 2));
    }
}
