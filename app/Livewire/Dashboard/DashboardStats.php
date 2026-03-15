<?php

namespace App\Livewire\Dashboard;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class DashboardStats extends Component
{
    #[Computed]
    public function todayAppointments(): int
    {
        return Appointment::today()
            ->when(auth()->user()->isMedecin(), fn ($q) => $q->forDoctor(auth()->id()))
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->count();
    }

    #[Computed]
    public function todayWaiting(): int
    {
        return Appointment::today()
            ->when(auth()->user()->isMedecin(), fn ($q) => $q->forDoctor(auth()->id()))
            ->whereIn('status', ['waiting', 'in_progress'])
            ->count();
    }

    #[Computed]
    public function totalPatients(): int
    {
        return Patient::active()->count();
    }

    #[Computed]
    public function newPatientsThisMonth(): int
    {
        return Patient::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    #[Computed]
    public function monthlyRevenue(): float
    {
        return Payment::where('status', 'paid')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('total_amount');
    }

    #[Computed]
    public function pendingPayments(): float
    {
        return Payment::whereIn('status', ['pending', 'partial'])
            ->sum('amount_due');
    }

    #[Computed]
    public function upcomingAppointments()
    {
        return Appointment::with(['patient', 'doctor'])
            ->upcoming()
            ->when(auth()->user()->isMedecin(), fn ($q) => $q->forDoctor(auth()->id()))
            ->orderBy('scheduled_at')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function recentConsultations()
    {
        return Consultation::with('patient')
            ->when(auth()->user()->isMedecin(), fn ($q) => $q->forDoctor(auth()->id()))
            ->orderByDesc('consultation_date')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function monthlyConsultationsChart(): array
    {
        $data = Consultation::selectRaw('DATE(consultation_date) as date, COUNT(*) as count')
            ->where('consultation_date', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $labels = [];
        $values = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('d/m');
            $values[] = $data[$date] ?? 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    public function render()
    {
        return view('livewire.dashboard.dashboard-stats', [
            'todayAppointments'       => $this->todayAppointments,
            'todayWaiting'            => $this->todayWaiting,
            'totalPatients'           => $this->totalPatients,
            'newPatientsThisMonth'    => $this->newPatientsThisMonth,
            'monthlyRevenue'          => $this->monthlyRevenue,
            'pendingPayments'         => $this->pendingPayments,
            'upcomingAppointments'    => $this->upcomingAppointments,
            'recentConsultations'     => $this->recentConsultations,
            'monthlyConsultationsChart' => $this->monthlyConsultationsChart,
        ])->layout('layouts.cabinet', [
                'title' => 'Tableau de bord',
                'header' => 'Tableau de bord',
                'headerIcon' => 'icon-home',
            ]);
    }
}
