<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSequentialNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalReport extends Model
{
    use GeneratesSequentialNumber, SoftDeletes;

    protected $fillable = [
        'report_number', 'patient_id', 'doctor_id', 'consultation_id',
        'type', 'title', 'content', 'report_date', 'valid_from', 'valid_until',
        'sick_leave_days', 'pdf_path', 'is_printed', 'created_by',
    ];

    protected $casts = [
        'report_date' => 'date',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_printed' => 'boolean',
    ];

    public const TYPE_LABELS = [
        'fitness_certificate' => 'Certificat d\'aptitude',
        'sick_leave' => 'Arrêt de travail',
        'medical_report' => 'Compte-rendu médical',
        'referral' => 'Lettre de référence',
        'other' => 'Autre',
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

    protected function sequentialNumberConfig(): array
    {
        return ['report_number', 'RPT'];
    }
}
