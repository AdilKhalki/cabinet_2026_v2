<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class MedicationIndex extends Component
{
    public function render()
    {
        return view('livewire.admin.medication-index')
            ->layout('layouts.cabinet', [
                'title' => 'Medicaments',
                'header' => 'Medicaments',
                'headerIcon' => 'icon-package',
                'breadcrumbs' => [['label' => 'Medicaments']],
            ]);
    }
}
