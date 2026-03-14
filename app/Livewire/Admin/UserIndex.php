<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class UserIndex extends Component
{
    public function render()
    {
        return view('livewire.admin.user-index')
            ->layout('layouts.cabinet', [
                'title' => 'Utilisateurs',
                'header' => 'Utilisateurs',
                'headerIcon' => 'icon-users',
                'breadcrumbs' => [['label' => 'Utilisateurs']],
            ]);
    }
}
