<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class AuthHardeningTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Intrus',
            'email' => 'intrus@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'intrus@example.com']);
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        User::factory()->create(['email' => 'off@cabinet.ma', 'is_active' => false]);

        $this->post(route('login.store'), ['email' => 'off@cabinet.ma', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_active_user_can_log_in_with_mixed_case_email(): void
    {
        $user = User::factory()->create(['email' => 'on@cabinet.ma']);

        $this->post(route('login.store'), ['email' => 'On@Cabinet.ma', 'password' => 'password'])
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'brute@cabinet.ma']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['email' => 'brute@cabinet.ma', 'password' => 'mauvais']);
        }

        $this->post(route('login.store'), ['email' => 'brute@cabinet.ma', 'password' => 'password'])
            ->assertStatus(429);
        $this->assertGuest();
    }

    public function test_app_timezone_is_casablanca(): void
    {
        // (la locale dépend de APP_LOCALE dans .env ; défaut « fr » dans config/app.php)
        $this->assertSame('Africa/Casablanca', config('app.timezone'));
        $this->assertSame('Africa/Casablanca', now()->getTimezone()->getName());
    }

    public function test_sidebar_hides_admin_links_from_secretary(): void
    {
        $this->actingAs($this->secretary());

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee(route('admin.users.index'))
            ->assertDontSee(route('medications.index'))
            ->assertDontSee(route('consultations.index'))
            ->assertSee(route('patients.index'));
    }

    public function test_sidebar_shows_admin_links_to_admin(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('admin.users.index'))
            ->assertSee(route('admin.roles.index'))
            ->assertSee(route('admin.activity-log'))
            ->assertSee(route('admin.settings'))
            ->assertSee(route('medical-acts.index'));
    }
}
