<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class CabinetSettings extends Component
{
    public function render()
    {
        return view('livewire.admin.cabinet-settings')
            ->layout('layouts.cabinet', [
                'title' => 'Parametres Cabinet',
                'header' => 'Parametres Cabinet',
                'headerIcon' => 'icon-settings',
                'breadcrumbs' => [['label' => 'Parametres Cabinet']],
            ]);
    }
}
