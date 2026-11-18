<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'sale_id',
        'doctor_name',
        'doctor_reg_no',
        'patient_name',
        'notes',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
