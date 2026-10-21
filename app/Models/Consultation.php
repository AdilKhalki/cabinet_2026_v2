<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Consultation extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id', 'doctor_id', 'appointment_id', 'consultation_type',
        'status', 'consultation_date', 'reason', 'symptoms', 'clinical_exam',
        'weight_kg', 'height_cm', 'blood_pressure', 'heart_rate', 'temperature',
        'spo2', 'diagnosis', 'icd10_code', 'treatment_plan', 'doctor_notes',
        'summary', 'followup_required', 'followup_date', 'created_by',
    ];

    protected $casts = [
        'consultation_date' => 'datetime',
        'followup_date' => 'date',
        'followup_required' => 'boolean',
        'weight_kg' => 'decimal:2',
        'height_cm' => 'decimal:1',
        'temperature' => 'decimal:1',
    ];

    public const STATUS_LABELS = [
        'in_progress' => 'En cours',
        'completed' => 'Terminée',
        'archived' => 'Archivée',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    // ── Accesseurs ────────────────────────────────────────────────
    public function getBmiAttribute(): ?float
    {
        if ($this->weight_kg && $this->height_cm) {
            $heightM = $this->height_cm / 100;

            return round($this->weight_kg / ($heightM * $heightM), 1);
        }

        return null;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? (string) $this->status;
    }

    public function getTypeLabelAttribute(): string
    {
        return Appointment::TYPE_LABELS[$this->consultation_type] ?? (string) $this->consultation_type;
    }

    // ── Relations ─────────────────────────────────────────────────
    public function patient(): BelongsTo
    {
        // withTrashed : l'historique médical reste consultable si le dossier est archivé
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function labTests(): HasMany
    {
        return $this->hasMany(LabTest::class);
    }

    public function medicalImages(): HasMany
    {
        return $this->hasMany(MedicalImage::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function medicalReports(): HasMany
    {
        return $this->hasMany(MedicalReport::class);
    }

    // ── Scopes ────────────────────────────────────────────────────
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeForDoctor($query, int $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }
}
