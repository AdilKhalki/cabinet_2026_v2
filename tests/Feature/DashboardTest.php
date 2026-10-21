<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_dashboard_alias_redirects_to_home(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('dashboard'))->assertRedirect('/');
    }

    public function test_each_role_can_visit_the_dashboard(): void
    {
        foreach (['admin', 'medecin', 'secretaire'] as $role) {
            $this->actingAs($this->userWithRole($role));
            $this->get(route('home'))->assertOk();
        }
    }
}
