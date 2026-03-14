<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Prescription extends Model
{
    use SoftDeletes, LogsActivity;

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

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->prescription_number)) {
                $year = date('Y');
                $count = static::withTrashed()->whereYear('created_at', $year)->count();
                $model->prescription_number = sprintf('ORD-%s-%05d', $year, $count + 1);
            }
        });
    }
}
