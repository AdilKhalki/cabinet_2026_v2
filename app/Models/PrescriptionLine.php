<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionLine extends Model
{
    protected $fillable = [
        'prescription_id', 'medication_id', 'medication_name',
        'dosage', 'frequency', 'duration_days', 'route',
        'instructions', 'quantity', 'sort_order',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }
}
