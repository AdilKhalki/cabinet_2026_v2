<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class ActivityLog extends Component
{
    public function render()
    {
        return view('livewire.admin.activity-log')
            ->layout('layouts.cabinet', [
                'title' => 'Journal Audit',
                'header' => 'Journal Audit',
                'headerIcon' => 'icon-eye',
                'breadcrumbs' => [['label' => 'Journal Audit']],
            ]);
    }
}
