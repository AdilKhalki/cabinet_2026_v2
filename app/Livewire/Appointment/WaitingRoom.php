<?php

namespace App\Livewire\Appointment;

use App\Models\Appointment;
use Livewire\Attributes\Computed;
use Livewire\Component;

class WaitingRoom extends Component
{
    // Polling toutes les 30 secondes pour la file d'attente en temps réel
    protected int $pollingInterval = 30000;

    public function callNext(int $appointmentId): void
    {
        $appt = Appointment::findOrFail($appointmentId);
        $appt->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function markCompleted(int $appointmentId): void
    {
        $appt = Appointment::findOrFail($appointmentId);
        $appt->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);
    }

    public function markNoShow(int $appointmentId): void
    {
        Appointment::findOrFail($appointmentId)->update(['status' => 'no_show']);
    }

    #[Computed]
    public function waitingPatients()
    {
        return Appointment::with('patient')
            ->waitingRoom()
            ->when(auth()->user()->isMedecin(), fn ($q) => $q->forDoctor(auth()->id()))
            ->orderBy('arrived_at')
            ->get();
    }

    #[Computed]
    public function todayStats()
    {
        $query = Appointment::today()
            ->when(auth()->user()->isMedecin(), fn ($q) => $q->forDoctor(auth()->id()));

        return [
            'total'       => (clone $query)->count(),
            'completed'   => (clone $query)->where('status', 'completed')->count(),
            'waiting'     => (clone $query)->whereIn('status', ['waiting', 'in_progress'])->count(),
            'cancelled'   => (clone $query)->where('status', 'cancelled')->count(),
        ];
    }

    public function render()
    {
        return view('livewire.appointment.waiting-room')
            ->layout('layouts.cabinet', [
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
