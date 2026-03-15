<?php

namespace App\Livewire\Notification;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationIndex extends Component
{
    use WithPagination;

    public string $filter = 'all';
    public int $perPage = 20;

    public function updatingFilter(): void { $this->resetPage(); }

    #[Computed]
    public function notifications()
    {
        $query = match ($this->filter) {
            'unread' => auth()->user()->unreadNotifications(),
            'read'   => auth()->user()->readNotifications(),
            default  => auth()->user()->notifications(),
        };

        return $query->paginate($this->perPage);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    public function markAsRead(string $id): void
    {
        $notif = auth()->user()->notifications()->find($id);
        $notif?->markAsRead();
        unset($this->notifications, $this->unreadCount);
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        session()->flash('success', 'Toutes les notifications marquées comme lues.');
        unset($this->notifications, $this->unreadCount);
    }

    public function delete(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->delete();
        unset($this->notifications, $this->unreadCount);
    }

    public function render()
    {
        return view('livewire.notification.notification-index', [
            'notifications'  => $this->notifications,
            'unreadCount'    => $this->unreadCount,
            'availableTypes' => [
                'all'    => 'Toutes',
                'unread' => 'Non lues',
                'read'   => 'Lues',
            ],
            'typeColors' => [
                'all'    => 'default',
                'unread' => 'danger',
                'read'   => 'success',
            ],
        ])->layout('layouts.cabinet', [
            'title'      => 'Notifications',
            'header'     => 'Notifications',
            'headerIcon' => 'icon-bell',
            'breadcrumbs' => [['label' => 'Notifications']],
        ]);
    }
}
