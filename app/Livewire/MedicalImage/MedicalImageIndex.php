<?php

namespace App\Livewire\MedicalImage;

use Livewire\Component;

class MedicalImageIndex extends Component
{
    public function render()
    {
        return view('livewire.medical-image.medical-image-index')
            ->layout('layouts.cabinet', [
                'title' => 'Imagerie',
                'header' => 'Imagerie',
                'headerIcon' => 'icon-image',
                'breadcrumbs' => [['label' => 'Imagerie']],
            ]);
    }
}
