<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalHistory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'type', 'title', 'description', 'date_occurred',
        'relation', 'habit_type', 'habit_frequency',
        'vaccine_name', 'vaccine_date', 'vaccine_next_date', 'vaccine_batch',
        'created_by',
    ];

    protected $casts = [
        'date_occurred' => 'date',
        'vaccine_date' => 'date',
        'vaccine_next_date' => 'date',
    ];

    public const TYPE_LABELS = [
        'medical'          => 'Antécédent médical',
        'surgical'         => 'Antécédent chirurgical',
        'hospitalization'  => 'Hospitalisation',
        'family'           => 'Antécédent familial',
        'allergy'          => 'Allergie',
        'vaccination'      => 'Vaccination',
        'habit'            => 'Habitude de vie',
    ];

    public const HABIT_LABELS = [
        'smoking'           => 'Tabac',
        'alcohol'           => 'Alcool',
        'physical_activity' => 'Activité physique',
        'other'             => 'Autre',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
