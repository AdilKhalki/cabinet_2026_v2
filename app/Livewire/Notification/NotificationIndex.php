<?php

namespace App\Livewire\Notification;

use Livewire\Component;

class NotificationIndex extends Component
{
    public function render()
    {
        return view('livewire.notification.notification-index')
            ->layout('layouts.cabinet', [
                'title' => 'Notifications',
                'header' => 'Notifications',
                'headerIcon' => 'icon-bell',
                'breadcrumbs' => [['label' => 'Notifications']],
            ]);
    }
}
