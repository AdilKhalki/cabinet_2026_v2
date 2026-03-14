<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class MedicalActIndex extends Component
{
    public function render()
    {
        return view('livewire.admin.medical-act-index')
            ->layout('layouts.cabinet', [
                'title' => 'Actes Medicaux',
                'header' => 'Actes Medicaux',
                'headerIcon' => 'icon-list',
                'breadcrumbs' => [['label' => 'Actes Medicaux']],
            ]);
    }
}
