<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MedicalImage extends Model
{
    use SoftDeletes;

    /** Disque privé : l'imagerie médicale ne doit pas être servie publiquement. */
    public const DISK = 'local';

    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

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

    /** Disque où se trouve le fichier (les anciens fichiers sont sur le disque public). */
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

    public function getIsImageAttribute(): bool
    {
        $ext = strtolower($this->file_type ?: pathinfo((string) $this->file_path, PATHINFO_EXTENSION));

        return in_array($ext, self::IMAGE_EXTENSIONS, true);
    }

    public function deleteFile(): void
    {
        if ($this->file_path) {
            Storage::disk($this->storageDisk())->delete($this->file_path);
        }
    }

    public function patient(): BelongsTo
    {
        // Inclure les patients archivés pour garder l'historique lisible
        return $this->belongsTo(Patient::class)->withTrashed();
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
