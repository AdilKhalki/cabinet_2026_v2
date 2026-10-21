<?php

namespace App\Livewire\Reports;

use App\Exports\PaymentsExport;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class FinancialReport extends Component
{
    public string $periodType = 'month';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $doctorFilter = '';

    public const PERIOD_LABELS = [
        'today' => "Aujourd'hui",
        'week' => 'Cette semaine',
        'month' => 'Ce mois',
        'quarter' => 'Ce trimestre',
        'year' => 'Cette année',
        'custom' => 'Personnalisée',
    ];

    public function mount(): void
    {
        $this->applyPeriod('month');
        $this->lockDoctorFilter();
    }

    public function resetFilters(): void
    {
        $this->doctorFilter = '';
        $this->applyPeriod('month');
        $this->lockDoctorFilter();
        $this->dispatchChart();
    }

    public function updated(string $property): void
    {
        if ($property === 'periodType') {
            $this->applyPeriod($this->periodType);
        } elseif (in_array($property, ['dateFrom', 'dateTo'], true)) {
            $this->periodType = 'custom';
        }

        $this->lockDoctorFilter();
        $this->dispatchChart();
    }

    /** Un médecin (non admin) ne voit que son propre chiffre d'affaires */
    private function lockDoctorFilter(): void
    {
        if (Payment::restrictedToOwn(auth()->user())) {
            $this->doctorFilter = (string) auth()->id();
        }
    }

    private function applyPeriod(string $type): void
    {
        $now = now();
        [$from, $to] = match ($type) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'custom' => [null, null],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };

        $this->periodType = array_key_exists($type, self::PERIOD_LABELS) ? $type : 'month';
        if ($from) {
            $this->dateFrom = $from->format('Y-m-d');
            $this->dateTo = $to->format('Y-m-d');
        }
    }

    /** Bornes de période valides (dates vides ou inversées corrigées) */
    private function period(): array
    {
        $from = $this->parseDate($this->dateFrom) ?? now()->startOfMonth();
        $to = $this->parseDate($this->dateTo) ?? now()->endOfMonth();

        return $from->lte($to) ? [$from, $to] : [$to, $from];
    }

    private function parseDate(string $value): ?Carbon
    {
        try {
            return $value !== '' ? Carbon::createFromFormat('Y-m-d', $value)->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Factures de la période, hors annulées / remboursées, filtrées par date de paiement */
    private function baseQuery()
    {
        [$from, $to] = $this->period();

        return Payment::query()
            ->revenue()
            ->forPeriod($from->format('Y-m-d'), $to->format('Y-m-d'))
            ->when($this->doctorFilter, fn ($q) => $q->where('doctor_id', $this->doctorFilter));
    }

    #[Computed]
    public function stats(): array
    {
        $row = $this->baseQuery()
            ->selectRaw("
                COALESCE(SUM(amount_paid), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN status IN ('pending','partial') THEN amount_due ELSE 0 END), 0) as total_pending,
                COUNT(*) as total_invoices,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial_count,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                COALESCE(AVG(total_amount), 0) as avg_invoice,
                COALESCE(SUM(insurance_coverage), 0) as total_insurance
            ")
            ->toBase()
            ->first();

        return [
            'total_revenue' => round((float) $row->total_revenue, 2),
            'total_pending' => round((float) $row->total_pending, 2),
            'total_invoices' => (int) $row->total_invoices,
            'paid_count' => (int) $row->paid_count,
            'partial_count' => (int) $row->partial_count,
            'pending_count' => (int) $row->pending_count,
            'avg_invoice' => round((float) $row->avg_invoice, 2),
            'total_insurance' => round((float) $row->total_insurance, 2),
        ];
    }

    #[Computed]
    public function paymentsByMethod(): array
    {
        return $this->baseQuery()
            ->groupBy('payment_method')
            ->selectRaw('payment_method, SUM(amount_paid) as total, COUNT(*) as count')
            ->orderByDesc('total')
            ->toBase()
            ->get()
            ->map(fn ($r) => [
                'label' => Payment::METHOD_LABELS[$r->payment_method] ?? 'Non précisé',
                'total' => round((float) $r->total, 2),
                'count' => (int) $r->count,
            ])
            ->toArray();
    }

    /** Évolution du CA : par jour (période ≤ 62 jours) sinon par mois */
    #[Computed]
    public function revenueSeries(): array
    {
        [$from, $to] = $this->period();
        $monthly = $from->diffInDays($to) > 62;

        // Regroupement par date (portable SQLite / MySQL), puis agrégation en PHP
        $rows = $this->baseQuery()
            ->groupBy('payment_date')
            ->selectRaw('payment_date, SUM(amount_paid) as total')
            ->toBase()
            ->get();

        $series = [];
        $cursor = $monthly ? $from->copy()->startOfMonth() : $from->copy();
        while ($cursor->lte($to)) {
            $series[$cursor->format($monthly ? 'Y-m' : 'Y-m-d')] = 0.0;
            $monthly ? $cursor->addMonthNoOverflow() : $cursor->addDay();
        }

        foreach ($rows as $row) {
            $key = Carbon::parse($row->payment_date)->format($monthly ? 'Y-m' : 'Y-m-d');
            if (array_key_exists($key, $series)) {
                $series[$key] += (float) $row->total;
            }
        }

        return [
            'labels' => array_map(
                fn ($k) => $monthly ? Carbon::createFromFormat('Y-m', $k)->translatedFormat('M Y') : Carbon::parse($k)->format('d/m'),
                array_keys($series)
            ),
            'data' => array_map(fn ($v) => round($v, 2), array_values($series)),
            'unit' => $monthly ? 'mois' : 'jour',
        ];
    }

    #[Computed]
    public function recentPayments()
    {
        return $this->baseQuery()
            ->with(['patient', 'doctor'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();
    }

    /** Export Excel des factures de la période */
    public function export()
    {
        abort_unless(auth()->user()->can('generate reports'), 403);

        [$from, $to] = $this->period();
        $this->lockDoctorFilter();

        return Excel::download(
            new PaymentsExport($this->baseQuery()),
            'rapport-financier-'.$from->format('Ymd').'-'.$to->format('Ymd').'.xlsx'
        );
    }

    private function dispatchChart(): void
    {
        unset($this->stats, $this->paymentsByMethod, $this->revenueSeries, $this->recentPayments);
        $this->dispatch('financial-chart-updated', series: $this->revenueSeries);
    }

    public function render()
    {
        $stats = $this->stats;
        [$from, $to] = $this->period();
        $doctorScope = fn ($q) => $q->when($this->doctorFilter, fn ($q) => $q->where('doctor_id', $this->doctorFilter));

        return view('livewire.reports.financial-report', [
            'stats' => $stats,
            'periodLabel' => $from->format('d/m/Y').' — '.$to->format('d/m/Y'),
            'caYear' => (float) Payment::revenue()->whereYear('payment_date', now()->year)->tap($doctorScope)->sum('amount_paid'),
            'totalInvoices' => Payment::revenue()->tap($doctorScope)->count(),
            'recentPayments' => $this->recentPayments,
            'paymentsByMethod' => $this->paymentsByMethod,
            'revenueSeries' => $this->revenueSeries,
            'statusColors' => ['paid' => 'label-success', 'partial' => 'label-warning', 'pending' => 'label-danger', 'cancelled' => 'label-default', 'refunded' => 'label-info'],
            'statusLabels' => ['paid' => 'Payé', 'partial' => 'Partiel', 'pending' => 'Impayé', 'cancelled' => 'Annulé', 'refunded' => 'Remboursé'],
            'doctors' => Payment::restrictedToOwn(auth()->user())
                ? User::whereKey(auth()->id())->get()
                : User::doctors()->active()->orderBy('name')->get(),
            'canExport' => auth()->user()->can('generate reports'),
        ])->layout('layouts.cabinet', [
            'title' => 'Rapport Financier',
            'header' => 'Rapport Financier',
            'headerIcon' => 'icon-trending-up',
            'breadcrumbs' => [['label' => 'Rapport Financier']],
        ]);
    }
}
