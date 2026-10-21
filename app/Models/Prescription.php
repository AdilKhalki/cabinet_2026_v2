<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSequentialNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Prescription extends Model
{
    use GeneratesSequentialNumber, LogsActivity, SoftDeletes;

    protected $fillable = [
        'prescription_number', 'patient_id', 'doctor_id', 'consultation_id',
        'prescribed_at', 'valid_until', 'notes', 'is_printed', 'pdf_path', 'created_by',
    ];

    protected $casts = [
        'prescribed_at' => 'date',
        'valid_until' => 'date',
        'is_printed' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

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

    public function lines(): HasMany
    {
        return $this->hasMany(PrescriptionLine::class)->orderBy('sort_order');
    }

    protected function sequentialNumberConfig(): array
    {
        return ['prescription_number', 'ORD'];
    }
}
