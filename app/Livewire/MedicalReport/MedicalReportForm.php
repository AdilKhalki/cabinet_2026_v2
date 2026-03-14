<?php

namespace App\Livewire\MedicalReport;

use Livewire\Component;

class MedicalReportForm extends Component
{
    public function render()
    {
        return view('livewire.medical-report.medical-report-form')
            ->layout('layouts.cabinet', [
                'title' => 'Nouveau Rapport',
                'header' => 'Nouveau Rapport',
                'headerIcon' => 'icon-clipboard',
                'breadcrumbs' => [['label' => 'Nouveau Rapport']],
            ]);
    }
}
