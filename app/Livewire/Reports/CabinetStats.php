<?php

namespace App\Livewire\Reports;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\LabTestResult;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PrescriptionLine;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CabinetStats extends Component
{
    public int $year;

    public int $month;

    public function mount(): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
    }

    private const MONTH_NAMES = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

    /** Expression SQL « mois » portable (MySQL / SQLite / PostgreSQL) */
    private function monthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%m', {$column}) AS INTEGER)",
            'pgsql' => "EXTRACT(MONTH FROM {$column})",
            default => "MONTH({$column})",
        };
    }

    /** Nombre d'enregistrements par mois (1..12) pour l'année sélectionnée */
    private function countByMonth($query, string $column): array
    {
        $expr = $this->monthExpression($column);
        $rows = $query->whereYear($column, $this->year)
            ->selectRaw("{$expr} as month, COUNT(*) as total")
            ->groupByRaw($expr)
            ->toBase()
            ->pluck('total', 'month');

        $counts = [];
        for ($m = 1; $m <= 12; $m++) {
            $counts[$m] = (int) ($rows[$m] ?? 0);
        }

        return $counts;
    }

    #[Computed]
    public function globalStats(): array
    {
        return [
            'total_patients' => Patient::count(),
            'active_patients' => Patient::active()->count(),
            'total_consultations' => Consultation::count(),
            'total_appointments' => Appointment::count(),
            'total_revenue' => Payment::revenue()->sum('amount_paid'),
            'new_patients_this_month' => Patient::whereYear('created_at', $this->year)
                ->whereMonth('created_at', $this->month)->count(),
            'consultations_this_month' => Consultation::whereYear('consultation_date', $this->year)
                ->whereMonth('consultation_date', $this->month)->count(),
        ];
    }

    #[Computed]
    public function monthlyConsultations(): array
    {
        $counts = $this->countByMonth(Consultation::query(), 'consultation_date');

        return ['labels' => self::MONTH_NAMES, 'data' => array_values($counts)];
    }

    #[Computed]
    public function appointmentsByStatus(): array
    {
        $rows = Appointment::groupBy('status')
            ->selectRaw('status, COUNT(*) as total')
            ->get();

        return [
            'labels' => $rows->pluck('status')->map(fn ($s) => Appointment::STATUS_LABELS[$s] ?? ucfirst((string) $s))->toArray(),
            'data' => $rows->pluck('total')->toArray(),
        ];
    }

    #[Computed]
    public function topDiagnoses(): array
    {
        return Consultation::whereNotNull('diagnosis')
            ->where('diagnosis', '!=', '')
            ->groupBy('diagnosis')
            ->selectRaw('diagnosis, COUNT(*) as total')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->toArray();
    }

    #[Computed]
    public function consultationsByType(): array
    {
        $rows = Consultation::groupBy('consultation_type')
            ->selectRaw('consultation_type, COUNT(*) as total')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('consultation_type')
                ->map(fn ($t) => Appointment::TYPE_LABELS[$t] ?? ($t ? ucfirst(str_replace('_', ' ', $t)) : 'Non précisé'))
                ->toArray(),
            'data' => $rows->pluck('total')->toArray(),
        ];
    }

    public function render()
    {
        $gs = $this->globalStats;

        // Nouveaux patients par mois de l'année (graphique)
        $npData = [];
        foreach ($this->countByMonth(Patient::query(), 'created_at') as $m => $total) {
            $npData[] = ['label' => self::MONTH_NAMES[$m - 1], 'value' => $total];
        }

        $cancelledCount = Appointment::where('status', 'cancelled')->count();
        $totalAppts = Appointment::count();

        return view('livewire.reports.cabinet-stats', [
            'totalPatients' => $gs['total_patients'],
            'newPatientsThisMonth' => $gs['new_patients_this_month'],
            'consultationsThisMonth' => $gs['consultations_this_month'],
            'consultationsThisYear' => Consultation::whereYear('consultation_date', $this->year)->count(),
            'appointmentsThisMonth' => Appointment::whereYear('scheduled_at', $this->year)->whereMonth('scheduled_at', $this->month)->count(),
            'pendingAppointments' => Appointment::whereIn('status', ['planned', 'confirmed', 'waiting'])->count(),
            'cancelledAppointments' => $cancelledCount,
            'cancellationRate' => $totalAppts > 0 ? round($cancelledCount / $totalAppts * 100, 1) : 0,
            'newPatientsPerMonth' => $npData,
            'consultationsByType' => $this->consultationsByType,
            'topMedications' => PrescriptionLine::groupBy('medication_name')
                ->selectRaw('medication_name, COUNT(*) as total')
                ->orderByDesc('total')->limit(10)->get(),
            'topLabTests' => LabTestResult::groupBy('parameter_name')
                ->selectRaw('parameter_name, COUNT(*) as total')
                ->orderByDesc('total')->limit(10)->get(),
        ])->layout('layouts.cabinet', [
            'title' => 'Statistiques Cabinet',
            'header' => 'Statistiques Cabinet',
            'headerIcon' => 'icon-bar-chart-2',
            'breadcrumbs' => [['label' => 'Statistiques Cabinet']],
        ]);
    }
}
