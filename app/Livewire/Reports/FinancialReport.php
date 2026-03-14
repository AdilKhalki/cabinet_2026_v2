<?php

namespace App\Livewire\Reports;

use Livewire\Component;

class FinancialReport extends Component
{
    public function render()
    {
        return view('livewire.reports.financial-report')
            ->layout('layouts.cabinet', [
                'title' => 'Rapport Financier',
                'header' => 'Rapport Financier',
                'headerIcon' => 'icon-trending-up',
                'breadcrumbs' => [['label' => 'Rapport Financier']],
            ]);
    }
}
