<?php

namespace App\Livewire\Appointment;

use App\Models\User;
use Livewire\Component;

/**
 * Calendrier FullCalendar. Les événements sont chargés côté client via
 * l'API JSON route('api.appointments.calendar') (filtrage par plage et par médecin).
 */
class AppointmentCalendar extends Component
{
    public function render()
    {
        $doctors = User::doctors()->active()->get();

        return view('livewire.appointment.appointment-calendar', [
            'doctors' => $doctors,
        ])
            ->layout('layouts.cabinet', [
                'title' => 'Calendrier',
                'header' => 'Calendrier des Rendez-vous',
                'headerIcon' => 'icon-calendar',
                'breadcrumbs' => [
                    ['label' => 'Rendez-vous', 'url' => route('appointments.index')],
                    ['label' => 'Calendrier'],
                ],
            ]);
    }
}
