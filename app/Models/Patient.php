<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSequentialNumber;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Patient extends Model
{
    use GeneratesSequentialNumber, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'dossier_number', 'first_name', 'last_name', 'cin', 'ip_number',
        'birth_date', 'gender', 'phone', 'phone_secondary', 'email',
        'address', 'city', 'blood_group', 'known_allergies', 'photo_path',
        'profession', 'insurance_name', 'insurance_number', 'is_active',
        'notes', 'created_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
    ];

    // ── Activité log ──────────────────────────────────────────────
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Patient {$eventName}");
    }

    // ── Accesseurs ────────────────────────────────────────────────
    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => "{$this->first_name} {$this->last_name}");
    }

    protected function age(): Attribute
    {
        return Attribute::get(fn () => $this->birth_date?->age);
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->photo_path
                ? Storage::disk('public')->url($this->photo_path)
                : null
        );
    }

    // ── Relations ─────────────────────────────────────────────────
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function medicalHistories(): HasMany
    {
        return $this->hasMany(MedicalHistory::class);
    }

    public function medicalReports(): HasMany
    {
        return $this->hasMany(MedicalReport::class);
    }

    // ── Scopes ────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Recherche multi-mots : chaque mot doit correspondre à au moins un champ,
     * ce qui permet de chercher « Ahmed Alami » comme « Alami Ahmed ».
     */
    public function scopeSearch($query, string $search)
    {
        $terms = preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($terms as $term) {
            $like = "%{$term}%";

            $query->where(function ($q) use ($like) {
                $q->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('phone_secondary', 'like', $like)
                    ->orWhere('dossier_number', 'like', $like)
                    ->orWhere('cin', 'like', $like)
                    ->orWhere('ip_number', 'like', $like);
            });
        }

        return $query;
    }

    // ── Boot ──────────────────────────────────────────────────────
    protected function sequentialNumberConfig(): array
    {
        return ['dossier_number', 'DOS'];
    }

    public static function generateDossierNumber(): string
    {
        return static::nextSequentialNumber('dossier_number', 'DOS');
    }
}
