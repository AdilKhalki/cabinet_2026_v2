<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSequentialNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabTest extends Model
{
    use GeneratesSequentialNumber, SoftDeletes;

    protected $fillable = [
        'request_number', 'patient_id', 'doctor_id', 'consultation_id',
        'requested_at', 'result_date', 'status', 'laboratory_name',
        'clinical_info', 'pdf_path', 'doctor_interpretation', 'created_by',
    ];

    protected $casts = [
        'requested_at' => 'date',
        'result_date' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(LabTestResult::class);
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->request_number)) {
                $model->request_number = static::nextSequentialNumber('request_number', 'BIO');
            }
        });
    }
}
