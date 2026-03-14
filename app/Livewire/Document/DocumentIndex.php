<?php

namespace App\Livewire\Document;

use Livewire\Component;

class DocumentIndex extends Component
{
    public function render()
    {
        return view('livewire.document.document-index')
            ->layout('layouts.cabinet', [
                'title' => 'Documents',
                'header' => 'Documents',
                'headerIcon' => 'icon-folder',
                'breadcrumbs' => [['label' => 'Documents']],
            ]);
    }
}
