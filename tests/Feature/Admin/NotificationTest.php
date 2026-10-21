<?php

namespace Tests\Feature\Admin;

use App\Livewire\Notification\NotificationIndex;
use App\Notifications\CabinetNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    public function test_notifications_page_lists_own_notifications(): void
    {
        $user = $this->secretary();
        $user->notify(new CabinetNotification('appointment', 'Nouveau RDV', 'RDV créé pour Ali'));
        $this->secretary()->notify(new CabinetNotification('payment', 'Paiement', 'Message d\'un autre utilisateur'));

        $this->actingAs($user);

        $this->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('RDV créé pour Ali')
            ->assertDontSee('Message d\'un autre utilisateur');
    }

    public function test_mark_as_read_and_mark_all_as_read(): void
    {
        $user = $this->doctor();
        $user->notify(new CabinetNotification('appointment', 'A', 'Premier'));
        $user->notify(new CabinetNotification('alert', 'B', 'Second'));
        $this->actingAs($user);

        $first = $user->notifications()->first();

        Livewire::test(NotificationIndex::class)
            ->assertSet('unreadCount', 2)
            ->call('markAsRead', $first->id)
            ->assertSet('unreadCount', 1)
            ->call('markAllAsRead')
            ->assertSet('unreadCount', 0);

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_user_cannot_touch_another_users_notification(): void
    {
        $owner = $this->doctor();
        $owner->notify(new CabinetNotification('alert', 'X', 'Privé'));
        $notification = $owner->notifications()->first();

        $this->actingAs($this->secretary());

        Livewire::test(NotificationIndex::class)
            ->call('markAsRead', $notification->id)
            ->call('delete', $notification->id);

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_status_and_type_filters(): void
    {
        $user = $this->admin();
        $user->notify(new CabinetNotification('appointment', 'RDV', 'Notif rendez-vous'));
        $user->notify(new CabinetNotification('payment', 'Paiement', 'Notif paiement'));
        $user->notifications()->where('data->type', 'payment')->first()->markAsRead();
        $this->actingAs($user);

        Livewire::test(NotificationIndex::class)
            ->set('filter', 'unread')
            ->assertSee('Notif rendez-vous')
            ->assertDontSee('Notif paiement')
            ->set('filter', 'all')
            ->set('typeFilter', 'payment')
            ->assertSee('Notif paiement')
            ->assertDontSee('Notif rendez-vous');
    }

    public function test_navbar_shows_unread_count(): void
    {
        $user = $this->secretary();
        $user->notify(new CabinetNotification('alert', 'Alerte navbar', 'msg'));
        $this->actingAs($user);

        $this->get(route('notifications.index'))->assertSee('Alerte navbar');
    }
}
