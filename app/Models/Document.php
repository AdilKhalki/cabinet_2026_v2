<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use SoftDeletes;

    /**
     * Disque privé (storage/app/private) : les documents médicaux ne doivent
     * jamais être accessibles par une URL publique devinable.
     */
    public const DISK = 'local';

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

    /**
     * Disque où se trouve réellement le fichier (les anciens fichiers
     * ont été enregistrés sur le disque public).
     */
    public function storageDisk(): string
    {
        return $this->file_path && Storage::disk(self::DISK)->exists($this->file_path)
            ? self::DISK
            : 'public';
    }

    /** URL temporaire signée (30 min) — à n'afficher qu'aux utilisateurs autorisés. */
    public function getFileUrlAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        $disk = $this->storageDisk();

        return $disk === self::DISK
            ? Storage::disk($disk)->temporaryUrl($this->file_path, now()->addMinutes(30))
            : Storage::disk($disk)->url($this->file_path);
    }

    public function deleteFile(): void
    {
        if ($this->file_path) {
            Storage::disk($this->storageDisk())->delete($this->file_path);
        }
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
        // Inclure les patients archivés pour garder l'historique lisible
        return $this->belongsTo(Patient::class)->withTrashed();
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
