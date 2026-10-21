<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSequentialNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Payment extends Model
{
    use GeneratesSequentialNumber, SoftDeletes, LogsActivity;

    protected $fillable = [
        'invoice_number', 'patient_id', 'doctor_id', 'consultation_id',
        'status', 'payment_method', 'subtotal', 'discount', 'insurance_coverage',
        'total_amount', 'amount_paid', 'amount_due', 'payment_date', 'due_date',
        'notes', 'pdf_path', 'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'insurance_coverage' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_due' => 'decimal:2',
    ];

    public const STATUS_LABELS = [
        'pending'   => 'En attente',
        'paid'      => 'Payé',
        'partial'   => 'Partiel',
        'cancelled' => 'Annulé',
        'refunded'  => 'Remboursé',
    ];

    public const METHOD_LABELS = [
        'cash'          => 'Espèces',
        'bank_transfer' => 'Virement',
        'insurance'     => 'Assurance/Mutuelle',
        'mixed'         => 'Mixte',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function patient(): BelongsTo
    {
        // Inclure les patients archivés : une facture doit rester consultable/imprimable
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

    public function lines(): HasMany
    {
        return $this->hasMany(PaymentLine::class);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['pending', 'partial']);
    }

    /** Statuts exclus du chiffre d'affaires */
    public const NON_REVENUE_STATUSES = ['cancelled', 'refunded'];

    public function scopeForPeriod($query, string $start, string $end)
    {
        // whereDate : portable (SQLite stocke « Y-m-d H:i:s ») et inclut le dernier jour
        return $query->whereDate('payment_date', '>=', $start)
            ->whereDate('payment_date', '<=', $end);
    }

    /** Factures comptant dans le chiffre d'affaires (hors annulées / remboursées) */
    public function scopeRevenue($query)
    {
        return $query->whereNotIn('status', self::NON_REVENUE_STATUSES);
    }

    /** Un médecin (non admin / non secrétaire) ne voit que ses propres factures */
    public function scopeVisibleTo($query, User $user)
    {
        return $query->when(
            static::restrictedToOwn($user),
            fn ($q) => $q->where('doctor_id', $user->id)
        );
    }

    public static function restrictedToOwn(User $user): bool
    {
        return $user->hasRole('medecin') && ! $user->hasAnyRole(['admin', 'secretaire']);
    }

    /** Statut cohérent avec les montants */
    public static function statusFor(float $total, float $paid): string
    {
        if ($total - $paid <= 0.004) {
            return 'paid';
        }

        return $paid > 0 ? 'partial' : 'pending';
    }

    public function isClosed(): bool
    {
        return in_array($this->status, self::NON_REVENUE_STATUSES, true);
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->invoice_number)) {
                $model->invoice_number = static::nextSequentialNumber('invoice_number', 'FAC');
            }
        });
    }
}
