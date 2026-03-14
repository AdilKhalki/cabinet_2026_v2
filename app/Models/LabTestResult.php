<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabTestResult extends Model
{
    protected $fillable = [
        'lab_test_id', 'lab_test_type_id', 'parameter_name',
        'value', 'unit', 'normal_min', 'normal_max', 'flag', 'notes',
    ];

    protected $casts = [
        'normal_min' => 'decimal:3',
        'normal_max' => 'decimal:3',
    ];

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class);
    }

    public function labTestType(): BelongsTo
    {
        return $this->belongsTo(LabTestType::class);
    }

    public function isAbnormal(): bool
    {
        return in_array($this->flag, ['low', 'high', 'critical']);
    }

    public function getFlagColorAttribute(): string
    {
        return match ($this->flag) {
            'low'      => 'text-info',
            'high'     => 'text-warning',
            'critical' => 'text-danger',
            default    => 'text-success',
        };
    }
}
