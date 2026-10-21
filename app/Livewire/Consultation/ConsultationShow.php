<?php

namespace App\Livewire\Consultation;

use App\Models\Consultation;
use Livewire\Component;

class ConsultationShow extends Component
{
    public Consultation $consultation;

    public function mount(Consultation $consultation): void
    {
        // Secret médical : un médecin ne consulte que ses propres consultations
        $this->authorize('view', $consultation);

        $this->consultation = $consultation->load([
            'patient', 'doctor', 'appointment', 'prescriptions.lines.medication',
            'labTests.results', 'medicalImages', 'documents', 'payment',
        ]);
    }

    /** Clôture une consultation en cours (et le RDV lié). */
    public function complete(): void
    {
        $this->authorize('update', $this->consultation);

        if ($this->consultation->status !== 'in_progress') {
            return;
        }

        $this->consultation->update(['status' => 'completed']);

        $appt = $this->consultation->appointment;
        if ($appt && $appt->canTransitionTo('completed')) {
            $appt->transitionTo('completed');
        }

        session()->flash('success', 'Consultation terminée.');
    }

    public function render()
    {
        return view('livewire.consultation.consultation-show')
            ->layout('layouts.cabinet', [
                'title' => 'Consultation',
                'header' => 'Détail de la Consultation',
                'headerIcon' => 'icon-activity',
                'breadcrumbs' => [
                    ['label' => 'Consultations', 'url' => route('consultations.index')],
                    ['label' => $this->consultation->patient->full_name],
                ],
            ]);
    }
}
