<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
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
