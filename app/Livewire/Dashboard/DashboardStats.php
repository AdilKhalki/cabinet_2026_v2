<?php

namespace App\Livewire\Dashboard;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class DashboardStats extends Component
{
    /** Médecin non admin : les indicateurs sont limités à son activité. */
    private function doctorScopeId(): ?int
    {
        $user = auth()->user();

        return $user->isMedecin() && ! $user->isAdmin() ? $user->id : null;
    }

    private function canSeeConsultations(): bool
    {
        $user = auth()->user();

        return $user->isMedecin() || $user->can('viewAny', Consultation::class);
    }

    private function canSeePayments(): bool
    {
        return auth()->user()->can('view payments');
    }

    /** Bornes du mois courant (requêtes indexables, contrairement à whereMonth/whereYear). */
    private function monthRange(): array
    {
        return [now()->startOfMonth(), now()->endOfMonth()];
    }

    #[Computed]
    public function todayStatusCounts()
    {
        // Une seule requête groupée pour les deux cartes « RDV du jour »
        return Appointment::today()
            ->when($this->doctorScopeId(), fn ($q, $id) => $q->forDoctor($id))
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
    }

    #[Computed]
    public function todayAppointments(): int
    {
        return (int) $this->todayStatusCounts
            ->except(Appointment::FREE_SLOT_STATUSES)
            ->sum();
    }

    #[Computed]
    public function todayWaiting(): int
    {
        return (int) $this->todayStatusCounts->only(['waiting', 'in_progress'])->sum();
    }

    #[Computed]
    public function totalPatients(): int
    {
        return Patient::active()->count();
    }

    #[Computed]
    public function newPatientsThisMonth(): int
    {
        return Patient::whereBetween('created_at', $this->monthRange())->count();
    }

    /** Encaissé ce mois : montants réellement perçus (payés + acomptes des paiements partiels). */
    #[Computed]
    public function monthlyRevenue(): float
    {
        if (! $this->canSeePayments()) {
            return 0.0;
        }

        return (float) Payment::whereIn('status', ['paid', 'partial'])
            ->whereBetween('payment_date', array_map(fn ($d) => $d->toDateString(), $this->monthRange()))
            ->when($this->doctorScopeId(), fn ($q, $id) => $q->where('doctor_id', $id))
            ->sum('amount_paid');
    }

    #[Computed]
    public function pendingPayments(): float
    {
        if (! $this->canSeePayments()) {
            return 0.0;
        }

        return (float) Payment::whereIn('status', ['pending', 'partial'])
            ->when($this->doctorScopeId(), fn ($q, $id) => $q->where('doctor_id', $id))
            ->sum('amount_due');
    }

    #[Computed]
    public function upcomingAppointments()
    {
        return Appointment::with(['patient', 'doctor'])
            ->upcoming()
            ->when($this->doctorScopeId(), fn ($q, $id) => $q->forDoctor($id))
            ->orderBy('scheduled_at')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function recentConsultations()
    {
        // La secrétaire n'a pas accès aux données médicales
        if (! $this->canSeeConsultations()) {
            return new Collection;
        }

        return Consultation::with(['patient', 'doctor'])
            ->when(auth()->user()->isMedecin(), fn ($q) => $q->forDoctor(auth()->id()))
            ->orderByDesc('consultation_date')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function monthlyConsultationsChart(): array
    {
        $data = [];

        if ($this->canSeeConsultations()) {
            $data = Consultation::selectRaw('DATE(consultation_date) as date, COUNT(*) as count')
                ->where('consultation_date', '>=', today()->subDays(29))
                ->when($this->doctorScopeId(), fn ($q, $id) => $q->forDoctor($id))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray();
        }

        $labels = [];
        $values = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = today()->subDays($i);
            $labels[] = $day->format('d/m');
            $values[] = (int) ($data[$day->format('Y-m-d')] ?? 0);
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
