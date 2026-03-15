<?php

namespace App\Livewire\Reports;

use App\Models\Appointment;
use App\Models\Consultation;
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

    #[Computed]
    public function globalStats(): array
    {
        return [
            'total_patients'         => Patient::count(),
            'active_patients'        => Patient::active()->count(),
            'total_consultations'    => Consultation::count(),
            'total_appointments'     => Appointment::count(),
            'total_revenue'          => Payment::where('status', '!=', 'cancelled')->sum('amount_paid'),
            'new_patients_this_month'=> Patient::whereYear('created_at', $this->year)
                                               ->whereMonth('created_at', $this->month)->count(),
            'consultations_this_month' => Consultation::whereYear('created_at', $this->year)
                                               ->whereMonth('created_at', $this->month)->count(),
        ];
    }

    #[Computed]
    public function monthlyConsultations(): array
    {
        $rows = Consultation::whereYear('created_at', $this->year)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $labels = [];
        $data = [];
        $monthNames = ['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Aoû','Sep','Oct','Nov','Déc'];

        for ($m = 1; $m <= 12; $m++) {
            $labels[] = $monthNames[$m - 1];
            $data[] = $rows->get($m)?->total ?? 0;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    #[Computed]
    public function appointmentsByStatus(): array
    {
        $rows = Appointment::groupBy('status')
            ->selectRaw('status, COUNT(*) as total')
            ->get();

        return [
            'labels' => $rows->pluck('status')->map(fn($s) => ucfirst($s))->toArray(),
            'data'   => $rows->pluck('total')->toArray(),
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
            'labels' => $rows->pluck('consultation_type')->map(fn($t) => ucfirst(str_replace('_', ' ', $t)))->toArray(),
            'data'   => $rows->pluck('total')->toArray(),
        ];
    }

    public function render()
    {
        $gs = $this->globalStats;

        // New patients per month for current year (for chart)
        $npRows = Patient::whereYear('created_at', $this->year)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->get()->keyBy('month');
        $monthNames = ['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Aoû','Sep','Oct','Nov','Déc'];
        $npData = [];
        for ($m = 1; $m <= 12; $m++) {
            $npData[] = ['label' => $monthNames[$m-1], 'value' => $npRows->get($m)?->total ?? 0];
        }

        $cancelledCount = Appointment::where('status', 'cancelled')->count();
        $totalAppts     = Appointment::count();

        return view('livewire.reports.cabinet-stats', [
            'totalPatients'           => $gs['total_patients'],
            'newPatientsThisMonth'    => $gs['new_patients_this_month'],
            'consultationsThisMonth'  => $gs['consultations_this_month'],
            'consultationsThisYear'   => Consultation::whereYear('created_at', $this->year)->count(),
            'appointmentsThisMonth'   => Appointment::whereYear('created_at', $this->year)->whereMonth('created_at', $this->month)->count(),
            'pendingAppointments'     => Appointment::whereIn('status', ['planned','confirmed','waiting'])->count(),
            'cancelledAppointments'   => $cancelledCount,
            'cancellationRate'        => $totalAppts > 0 ? round($cancelledCount / $totalAppts * 100, 1) : 0,
            'newPatientsPerMonth'     => $npData,
            'consultationsByType'     => $this->consultationsByType,
            'topMedications'          => PrescriptionLine::groupBy('medication_name')
                ->selectRaw('medication_name, COUNT(*) as total')
                ->orderByDesc('total')->limit(10)->get(),
            'topLabTests'             => \App\Models\LabTestResult::groupBy('parameter_name')
                ->selectRaw('parameter_name, COUNT(*) as total')
                ->orderByDesc('total')->limit(10)->get(),
        ])->layout('layouts.cabinet', [
            'title'      => 'Statistiques Cabinet',
            'header'     => 'Statistiques Cabinet',
            'headerIcon' => 'icon-bar-chart-2',
            'breadcrumbs' => [['label' => 'Statistiques Cabinet']],
        ]);
    }
}
