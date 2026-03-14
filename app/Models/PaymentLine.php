<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLine extends Model
{
    protected $fillable = [
        'payment_id', 'medical_act_id', 'description',
        'quantity', 'unit_price', 'total_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function medicalAct(): BelongsTo
    {
        return $this->belongsTo(MedicalAct::class);
    }
}
