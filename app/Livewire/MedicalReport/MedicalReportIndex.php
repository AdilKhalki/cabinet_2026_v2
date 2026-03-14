<?php

namespace App\Livewire\MedicalReport;

use Livewire\Component;

class MedicalReportIndex extends Component
{
    public function render()
    {
        return view('livewire.medical-report.medical-report-index')
            ->layout('layouts.cabinet', [
                'title' => 'Rapports Medicaux',
                'header' => 'Rapports Medicaux',
                'headerIcon' => 'icon-clipboard',
                'breadcrumbs' => [['label' => 'Rapports Medicaux']],
            ]);
    }
}
