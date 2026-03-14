<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Payment extends Model
{
    use SoftDeletes, LogsActivity;

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

    public function lines(): HasMany
    {
        return $this->hasMany(PaymentLine::class);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['pending', 'partial']);
    }

    public function scopeForPeriod($query, string $start, string $end)
    {
        return $query->whereBetween('payment_date', [$start, $end]);
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->invoice_number)) {
                $year = date('Y');
                $count = static::withTrashed()->whereYear('created_at', $year)->count();
                $model->invoice_number = sprintf('FAC-%s-%05d', $year, $count + 1);
            }
        });
    }
}
