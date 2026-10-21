<?php

namespace App\Livewire\MedicalReport;

use App\Models\MedicalReport;
use Livewire\Component;

class MedicalReportShow extends Component
{
    public MedicalReport $medicalReport;

    public function mount(MedicalReport $medicalReport): void
    {
        $this->authorize('view', $medicalReport);

        $this->medicalReport = $medicalReport->load(['patient', 'doctor', 'consultation']);
    }

    public function render()
    {
        return view('livewire.medical-report.medical-report-show', [
            'report'     => $this->medicalReport,
            'typeLabels' => MedicalReport::TYPE_LABELS,
        ])->layout('layouts.cabinet', [
            'title'      => 'Rapport Médical',
            'header'     => $this->medicalReport->title,
            'headerIcon' => 'icon-clipboard',
            'breadcrumbs' => [
                ['label' => 'Rapports Médicaux', 'url' => route('medical-reports.index')],
                ['label' => $this->medicalReport->report_number],
            ],
        ]);
    }
}
