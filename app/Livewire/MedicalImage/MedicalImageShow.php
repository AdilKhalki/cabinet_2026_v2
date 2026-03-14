<?php

namespace App\Livewire\MedicalImage;

use Livewire\Component;

class MedicalImageShow extends Component
{
    public function render()
    {
        return view('livewire.medical-image.medical-image-show')
            ->layout('layouts.cabinet', [
                'title' => 'Image Medicale',
                'header' => 'Image Medicale',
                'headerIcon' => 'icon-image',
                'breadcrumbs' => [['label' => 'Image Medicale']],
            ]);
    }
}
