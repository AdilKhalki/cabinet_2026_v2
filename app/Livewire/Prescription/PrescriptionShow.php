<?php

namespace App\Livewire\Prescription;

use App\Models\Prescription;
use Livewire\Component;

class PrescriptionShow extends Component
{
    public Prescription $prescription;

    public function mount(Prescription $prescription): void
    {
        $this->prescription = $prescription->load(['patient', 'doctor', 'lines.medication', 'consultation']);
    }

    public function render()
    {
        return view('livewire.prescription.prescription-show')
            ->layout('layouts.cabinet', [
                'title' => "Ordonnance {$this->prescription->prescription_number}",
                'header' => "Ordonnance {$this->prescription->prescription_number}",
                'headerIcon' => 'icon-file-text',
                'breadcrumbs' => [
                    ['label' => 'Ordonnances', 'url' => route('prescriptions.index')],
                    ['label' => $this->prescription->prescription_number],
                ],
            ]);
    }
}
