<?php

namespace App\Livewire\Reports;

use Livewire\Component;

class CabinetStats extends Component
{
    public function render()
    {
        return view('livewire.reports.cabinet-stats')
            ->layout('layouts.cabinet', [
                'title' => 'Statistiques Cabinet',
                'header' => 'Statistiques Cabinet',
                'headerIcon' => 'icon-bar-chart-2',
                'breadcrumbs' => [['label' => 'Statistiques Cabinet']],
            ]);
    }
}
