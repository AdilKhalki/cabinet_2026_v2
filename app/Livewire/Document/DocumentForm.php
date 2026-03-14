<?php

namespace App\Livewire\Document;

use Livewire\Component;

class DocumentForm extends Component
{
    public function render()
    {
        return view('livewire.document.document-form')
            ->layout('layouts.cabinet', [
                'title' => 'Nouveau Document',
                'header' => 'Nouveau Document',
                'headerIcon' => 'icon-folder',
                'breadcrumbs' => [['label' => 'Nouveau Document']],
            ]);
    }
}
