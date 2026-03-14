<?php

namespace App\Livewire\MedicalImage;

use Livewire\Component;

class MedicalImageForm extends Component
{
    public function render()
    {
        return view('livewire.medical-image.medical-image-form')
            ->layout('layouts.cabinet', [
                'title' => 'Nouvelle Image',
                'header' => 'Nouvelle Image',
                'headerIcon' => 'icon-image',
                'breadcrumbs' => [['label' => 'Nouvelle Image']],
            ]);
    }
}
