<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'consultation_id', 'category', 'title', 'description',
        'file_path', 'file_name', 'file_type', 'file_size', 'document_date',
        'source', 'uploaded_by',
    ];

    protected $casts = ['document_date' => 'date'];

    public const CATEGORY_LABELS = [
        'medical_certificate'    => 'Certificat médical',
        'hospital_report'        => 'Compte-rendu hospitalier',
        'external_results'       => 'Résultats externes',
        'insurance'              => 'Assurance / Mutuelle',
        'prescription_external'  => 'Ordonnance externe',
        'other'                  => 'Autre',
    ];

    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' Mo';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' Ko';
        return $bytes . ' o';
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
