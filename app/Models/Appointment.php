<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Appointment extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'patient_id', 'doctor_id', 'created_by', 'scheduled_at',
        'duration_minutes', 'consultation_type', 'status',
        'cancellation_reason', 'notes', 'color', 'reminder_sent',
        'arrived_at', 'started_at', 'ended_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'arrived_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'reminder_sent' => 'boolean',
    ];

    // Labels pour l'affichage
    public const STATUS_LABELS = [
        'planned'     => 'Planifié',
        'confirmed'   => 'Confirmé',
        'waiting'     => 'En attente',
        'in_progress' => 'En cours',
        'completed'   => 'Terminé',
        'cancelled'   => 'Annulé',
        'no_show'     => 'Absent',
    ];

    public const STATUS_COLORS = [
        'planned'     => 'label-primary',
        'confirmed'   => 'label-info',
        'waiting'     => 'label-warning',
        'in_progress' => 'bg-c-blue',
        'completed'   => 'label-success',
        'cancelled'   => 'label-danger',
        'no_show'     => 'label-default',
    ];

    public const TYPE_LABELS = [
        'general'         => 'Consultation générale',
        'specialized'     => 'Consultation spécialisée',
        'followup'        => 'Suivi',
        'emergency'       => 'Urgence',
        'control'         => 'Contrôle',
        'teleconsultation' => 'Téléconsultation',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    // ── Accesseurs ────────────────────────────────────────────────
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'label-default';
    }

    public function getScheduledEndAttribute()
    {
        return $this->scheduled_at?->addMinutes($this->duration_minutes);
    }

    // ── Relations ─────────────────────────────────────────────────
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class);
    }

    // ── Scopes ────────────────────────────────────────────────────
    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>=', now())
                     ->whereIn('status', ['planned', 'confirmed']);
    }

    public function scopeWaitingRoom($query)
    {
        return $query->whereIn('status', ['waiting', 'in_progress'])
                     ->whereDate('scheduled_at', today());
    }

    public function scopeForDoctor($query, int $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeForCalendar($query, string $start, string $end)
    {
        return $query->whereBetween('scheduled_at', [$start, $end]);
    }
}
