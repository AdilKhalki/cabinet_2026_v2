<?php

namespace App\Livewire\Patient;

use App\Models\Patient;
use Livewire\Component;

class PatientShow extends Component
{
    public Patient $patient;
    public string $activeTab = 'overview';

    public function mount(Patient $patient): void
    {
        $this->patient = $patient->load([
            'consultations.prescriptions',
            'appointments',
            'medicalHistories',
            'labTests.results',
            'medicalImages',
            'documents',
            'payments',
            'medicalReports',
        ]);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.patient.patient-show')
            ->layout('layouts.cabinet', [
                'title' => "Dossier — {$this->patient->full_name}",
                'header' => "Dossier Patient : {$this->patient->full_name}",
                'headerIcon' => 'icon-user',
                'subHeader' => "Dossier N° {$this->patient->dossier_number}",
                'breadcrumbs' => [
                    ['label' => 'Patients', 'url' => route('patients.index')],
                    ['label' => $this->patient->full_name],
                ],
            ]);
    }
}
