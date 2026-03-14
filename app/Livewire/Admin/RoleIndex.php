<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class RoleIndex extends Component
{
    public function render()
    {
        return view('livewire.admin.role-index')
            ->layout('layouts.cabinet', [
                'title' => 'Roles',
                'header' => 'Roles',
                'headerIcon' => 'icon-shield',
                'breadcrumbs' => [['label' => 'Roles']],
            ]);
    }
}
