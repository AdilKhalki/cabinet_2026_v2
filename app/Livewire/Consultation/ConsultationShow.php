<?php

namespace App\Livewire\Consultation;

use App\Models\Consultation;
use Livewire\Component;

class ConsultationShow extends Component
{
    public Consultation $consultation;

    public function mount(Consultation $consultation): void
    {
        $this->consultation = $consultation->load([
            'patient', 'doctor', 'prescriptions.lines.medication',
            'labTests.results', 'medicalImages', 'documents', 'payment',
        ]);
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
