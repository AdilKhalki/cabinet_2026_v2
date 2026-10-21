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

    /**
     * Transitions de statut autorisées (statut actuel => statuts cibles).
     */
    public const STATUS_TRANSITIONS = [
        'planned'     => ['confirmed', 'waiting', 'in_progress', 'cancelled', 'no_show'],
        'confirmed'   => ['planned', 'waiting', 'in_progress', 'cancelled', 'no_show'],
        'waiting'     => ['in_progress', 'completed', 'cancelled', 'no_show'],
        'in_progress' => ['waiting', 'completed'],
        'completed'   => [],
        'cancelled'   => ['planned'],
        'no_show'     => ['planned'],
    ];

    /** Statuts qui n'occupent plus le créneau du médecin. */
    public const FREE_SLOT_STATUSES = ['cancelled', 'no_show'];

    /** Durée maximale d'un RDV (minutes), utilisée pour borner la recherche de chevauchements. */
    public const MAX_DURATION = 480;

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

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::STATUS_TRANSITIONS[$this->status] ?? [], true);
    }

    /**
     * Change le statut en renseignant les horodatages associés.
     * Lève une exception si la transition n'est pas autorisée.
     */
    public function transitionTo(string $status, array $attributes = []): void
    {
        if (! $this->canTransitionTo($status)) {
            throw new \DomainException("Transition de statut interdite : {$this->status} → {$status}.");
        }

        $now = now();
        $attributes['status'] = $status;

        if ($status === 'waiting' && ! $this->arrived_at) {
            $attributes['arrived_at'] = $now;
        }
        if ($status === 'in_progress' && ! $this->started_at) {
            $attributes['started_at'] = $now;
        }
        if ($status === 'completed') {
            $attributes['started_at'] = $this->started_at ?? $now;
            $attributes['ended_at'] = $now;
        }
        if ($status === 'planned') {
            // Réactivation d'un RDV annulé / absent
            $attributes['cancellation_reason'] = null;
        }

        $this->update($attributes);
    }

    /**
     * RDV actifs du médecin qui chevauchent le créneau [début, début + durée[.
     */
    public static function overlapping(int $doctorId, $start, int $durationMinutes, ?int $ignoreId = null)
    {
        $start = \Illuminate\Support\Facades\Date::parse($start);
        $end = $start->addMinutes($durationMinutes);

        // Pré-filtrage SQL portable (indexé sur doctor_id + scheduled_at), puis calcul exact en PHP
        return self::query()
            ->with('patient')
            ->where('doctor_id', $doctorId)
            ->whereNotIn('status', self::FREE_SLOT_STATUSES)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where('scheduled_at', '<', $end)
            ->where('scheduled_at', '>', $start->subMinutes(self::MAX_DURATION))
            ->get()
            ->filter(fn (self $appt) => $appt->scheduled_end->greaterThan($start))
            ->values();
    }

    // ── Relations ─────────────────────────────────────────────────
    public function patient(): BelongsTo
    {
        // withTrashed : un RDV doit rester affichable même si le dossier patient est archivé
        return $this->belongsTo(Patient::class)->withTrashed();
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
        // whereBetween (et non whereDate) pour utiliser l'index sur scheduled_at
        return $query->whereBetween('scheduled_at', [today(), today()->endOfDay()]);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>=', now())
                     ->whereIn('status', ['planned', 'confirmed']);
    }

    public function scopeWaitingRoom($query)
    {
        return $query->whereIn('status', ['waiting', 'in_progress'])
                     ->whereBetween('scheduled_at', [today(), today()->endOfDay()]);
    }

    public function scopeForDoctor($query, int $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /** RDV commençant dans l'intervalle [start, end[ (end exclusif, comme FullCalendar). */
    public function scopeForCalendar($query, $start, $end)
    {
        return $query->where('scheduled_at', '>=', $start)
                     ->where('scheduled_at', '<', $end);
    }
}
