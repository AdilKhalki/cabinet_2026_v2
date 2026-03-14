<?php

namespace App\Livewire\Appointment;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Livewire\Component;

class AppointmentCalendar extends Component
{
    public string $doctorFilter = '';

    public function getEventsForCalendar(string $start, string $end): array
    {
        return Appointment::with(['patient', 'doctor'])
            ->forCalendar($start, $end)
            ->when(auth()->user()->isMedecin(), fn ($q) => $q->forDoctor(auth()->id()))
            ->when($this->doctorFilter, fn ($q) => $q->where('doctor_id', $this->doctorFilter))
            ->get()
            ->map(fn ($appt) => [
                'id'              => $appt->id,
                'title'           => $appt->patient->full_name,
                'start'           => $appt->scheduled_at->toIso8601String(),
                'end'             => $appt->scheduled_end?->toIso8601String(),
                'backgroundColor' => $appt->color ?? '#01a9ac',
                'borderColor'     => $appt->color ?? '#01a9ac',
                'extendedProps'   => [
                    'status'          => $appt->status,
                    'status_label'    => $appt->status_label,
                    'doctor'          => $appt->doctor->name,
                    'type'            => Appointment::TYPE_LABELS[$appt->consultation_type] ?? '',
                    'dossier'         => $appt->patient->dossier_number,
                ],
            ])
            ->toArray();
    }

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
