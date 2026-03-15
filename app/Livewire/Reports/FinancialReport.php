<?php

namespace App\Livewire\Reports;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class FinancialReport extends Component
{
    public string $periodType = 'month';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $doctorFilter = '';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function resetFilters(): void
    {
        $this->periodType   = 'month';
        $this->doctorFilter = '';
        $this->dateFrom     = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo       = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatedPeriodType(): void
    {
        match ($this->periodType) {
            'month'   => [$this->dateFrom, $this->dateTo] = [now()->startOfMonth()->format('Y-m-d'), now()->endOfMonth()->format('Y-m-d')],
            'quarter' => [$this->dateFrom, $this->dateTo] = [now()->startOfQuarter()->format('Y-m-d'), now()->endOfQuarter()->format('Y-m-d')],
            'year'    => [$this->dateFrom, $this->dateTo] = [now()->startOfYear()->format('Y-m-d'), now()->endOfYear()->format('Y-m-d')],
            default   => null,
        };
        unset($this->stats, $this->paymentsByMethod, $this->dailyRevenue, $this->recentPayments);
    }

    public function updatedDoctorFilter(): void
    {
        unset($this->stats, $this->paymentsByMethod, $this->dailyRevenue, $this->recentPayments);
    }

    public function updatedDateFrom(): void
    {
        unset($this->stats, $this->paymentsByMethod, $this->dailyRevenue, $this->recentPayments);
    }

    public function updatedDateTo(): void
    {
        unset($this->stats, $this->paymentsByMethod, $this->dailyRevenue, $this->recentPayments);
    }

    private function baseQuery()
    {
        return Payment::whereBetween(DB::raw('DATE(created_at)'), [$this->dateFrom, $this->dateTo])
            ->where('status', '!=', 'cancelled')
            ->when($this->doctorFilter, fn($q) => $q->where('doctor_id', $this->doctorFilter));
    }

    #[Computed]
    public function stats(): array
    {
        $base = $this->baseQuery();

        return [
            'total_revenue'   => (clone $base)->sum('amount_paid'),
            'total_pending'   => (clone $base)->where('amount_due', '>', 0)->sum('amount_due'),
            'total_invoices'  => (clone $base)->count(),
            'paid_count'      => (clone $base)->where('status', 'paid')->count(),
            'partial_count'   => (clone $base)->where('status', 'partial')->count(),
            'pending_count'   => (clone $base)->where('status', 'pending')->count(),
            'avg_invoice'     => (clone $base)->avg('total_amount') ?? 0,
        ];
    }

    #[Computed]
    public function paymentsByMethod(): array
    {
        return $this->baseQuery()
            ->groupBy('payment_method')
            ->selectRaw('payment_method, SUM(amount_paid) as total, COUNT(*) as count')
            ->get()
            ->toArray();
    }

    #[Computed]
    public function dailyRevenue(): array
    {
        $rows = $this->baseQuery()
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('DATE(created_at) as day, SUM(amount_paid) as total')
            ->orderBy('day')
            ->get();

        return [
            'labels' => $rows->pluck('day')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))->toArray(),
            'data'   => $rows->pluck('total')->map(fn($v) => round($v, 2))->toArray(),
        ];
    }

    #[Computed]
    public function recentPayments()
    {
        return $this->baseQuery()
            ->with('patient')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    public function render()
    {
        $stats = $this->stats;

        return view('livewire.reports.financial-report', [
            'caMonth'           => $stats['total_revenue'],
            'caYear'            => Payment::whereYear('created_at', now()->year)->where('status', '!=', 'cancelled')->sum('amount_paid'),
            'invoicesThisMonth' => $stats['total_invoices'],
            'countUnpaid'       => $stats['pending_count'],
            'totalInvoices'     => Payment::where('status', '!=', 'cancelled')->count(),
            'recentPayments'    => $this->recentPayments,
            'monthlyRevenueData'=> $this->dailyRevenue,
            'statusColors'      => ['paid' => 'label-success', 'partial' => 'label-warning', 'pending' => 'label-danger', 'cancelled' => 'label-default', 'refunded' => 'label-info'],
            'statusLabels'      => ['paid' => 'Payé', 'partial' => 'Partiel', 'pending' => 'Impayé', 'cancelled' => 'Annulé', 'refunded' => 'Remboursé'],
            'doctors'           => User::doctors()->active()->orderBy('name')->get(),
        ])->layout('layouts.cabinet', [
            'title'      => 'Rapport Financier',
            'header'     => 'Rapport Financier',
            'headerIcon' => 'icon-trending-up',
            'breadcrumbs' => [['label' => 'Rapport Financier']],
        ]);
    }
}
