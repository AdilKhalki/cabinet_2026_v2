<?php

namespace App\Livewire\Appointment;

use App\Models\Appointment;
use Livewire\Attributes\Computed;
use Livewire\Component;

class WaitingRoom extends Component
{
    // Rafraîchissement via wire:poll.30000ms dans la vue

    /** Le patient se présente à l'accueil : il entre en salle d'attente. */
    public function markArrived(int $appointmentId): void
    {
        $this->changeStatus($appointmentId, 'waiting');
    }

    /** Le médecin appelle le patient : la consultation commence. */
    public function callNext(int $appointmentId): void
    {
        $this->changeStatus($appointmentId, 'in_progress');
    }

    public function markCompleted(int $appointmentId): void
    {
        $appt = $this->changeStatus($appointmentId, 'completed');

        // Clôturer la consultation liée encore ouverte
        $appt?->consultation()->where('status', 'in_progress')->update(['status' => 'completed']);
    }

    public function markNoShow(int $appointmentId): void
    {
        $this->changeStatus($appointmentId, 'no_show');
    }

    private function changeStatus(int $appointmentId, string $status): ?Appointment
    {
        $appt = Appointment::findOrFail($appointmentId);
        $this->authorize('update', $appt);

        if (! $appt->canTransitionTo($status)) {
            session()->flash('error', 'Action impossible : le rendez-vous est « '.$appt->status_label.' ».');

            return null;
        }

        $appt->transitionTo($status);

        return $appt;
    }

    private function scopedToday()
    {
        return Appointment::today()
            ->when(auth()->user()->isMedecin(), fn ($q) => $q->forDoctor(auth()->id()));
    }

    #[Computed]
    public function waitingPatients()
    {
        return Appointment::with(['patient', 'doctor', 'consultation:id,appointment_id,status'])
            ->waitingRoom()
            ->when(auth()->user()->isMedecin(), fn ($q) => $q->forDoctor(auth()->id()))
            // Patients en consultation en premier, puis ordre d'arrivée
            ->orderByRaw("CASE WHEN status = 'in_progress' THEN 0 ELSE 1 END")
            ->orderBy('arrived_at')
            ->get();
    }

    /** RDV du jour pas encore arrivés (pour pointer l'arrivée). */
    #[Computed]
    public function expectedPatients()
    {
        return $this->scopedToday()
            ->with(['patient', 'doctor'])
            ->whereIn('status', ['planned', 'confirmed'])
            ->orderBy('scheduled_at')
            ->get();
    }

    #[Computed]
    public function todayStats()
    {
        // Une seule requête groupée au lieu de quatre COUNT
        $counts = $this->scopedToday()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'total'     => (int) $counts->sum(),
            'completed' => (int) ($counts['completed'] ?? 0),
            'waiting'   => (int) (($counts['waiting'] ?? 0) + ($counts['in_progress'] ?? 0)),
            'cancelled' => (int) (($counts['cancelled'] ?? 0) + ($counts['no_show'] ?? 0)),
        ];
    }

    public function render()
    {
        return view('livewire.appointment.waiting-room', [
            'waitingPatients'  => $this->waitingPatients,
            'expectedPatients' => $this->expectedPatients,
            'todayStats'       => $this->todayStats,
        ])->layout('layouts.cabinet', [
                'title' => 'Salle d\'attente',
                'header' => 'Salle d\'Attente — ' . now()->format('d/m/Y'),
                'headerIcon' => 'icon-users',
                'breadcrumbs' => [
                    ['label' => 'Rendez-vous', 'url' => route('appointments.index')],
                    ['label' => 'Salle d\'attente'],
                ],
            ]);
    }
}
