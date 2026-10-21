<?php

namespace App\Exports;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Export Excel des factures d'une période (rapport financier).
 */
class PaymentsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private Builder $query) {}

    public function query()
    {
        return $this->query->with(['patient', 'doctor'])->orderBy('payment_date')->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'N° Facture', 'Date', 'Patient', 'N° Dossier', 'Médecin', 'Mode de paiement', 'Statut',
            'Sous-total (MAD)', 'Remise (MAD)', 'Part assurance (MAD)', 'Total (MAD)', 'Payé (MAD)', 'Reste (MAD)',
        ];
    }

    /** @param  Payment  $payment */
    public function map($payment): array
    {
        return [
            $payment->invoice_number,
            $payment->payment_date?->format('d/m/Y'),
            $payment->patient?->full_name,
            $payment->patient?->dossier_number,
            $payment->doctor?->name,
            Payment::METHOD_LABELS[$payment->payment_method] ?? $payment->payment_method,
            Payment::STATUS_LABELS[$payment->status] ?? $payment->status,
            (float) $payment->subtotal,
            (float) $payment->discount,
            (float) $payment->insurance_coverage,
            (float) $payment->total_amount,
            (float) $payment->amount_paid,
            (float) $payment->amount_due,
        ];
    }
}
