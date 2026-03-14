<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MedicalImage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'doctor_id', 'consultation_id', 'category', 'title',
        'description', 'file_path', 'file_type', 'file_size',
        'radiological_report', 'image_date', 'body_part', 'created_by',
    ];

    protected $casts = ['image_date' => 'date'];

    public const CATEGORY_LABELS = [
        'chest_xray'    => 'Radiographie Thorax',
        'abdominal_xray'=> 'Radiographie Abdomen',
        'limb_xray'     => 'Radiographie Membres',
        'ct_scan'       => 'Scanner (TDM)',
        'mri'           => 'IRM',
        'ultrasound'    => 'Échographie',
        'other'         => 'Autre',
    ];

    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
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
}
