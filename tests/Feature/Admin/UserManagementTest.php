<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\UserIndex;
use App\Models\Consultation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
    }

    public function test_admin_page_renders(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.users.index'))->assertOk()->assertSee('Gestion des Utilisateurs');
    }

    public function test_admin_creates_a_user_with_a_role(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(UserIndex::class)
            ->call('openCreate')
            ->assertSet('showModal', true)
            ->set('name', 'Dr. Salma Bennani')
            ->set('email', 'Salma@Cabinet.ma')
            ->set('role', 'medecin')
            ->set('speciality', 'Pédiatrie')
            ->set('password', 'Secret-2026!')
            ->set('password_confirmation', 'Secret-2026!')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        $user = User::where('email', 'salma@cabinet.ma')->firstOrFail();
        $this->assertTrue($user->hasRole('medecin'));
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('Pédiatrie', $user->speciality);
    }

    public function test_user_creation_requires_confirmed_password_and_existing_role(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(UserIndex::class)
            ->call('openCreate')
            ->set('name', 'X')
            ->set('email', 'x@cabinet.ma')
            ->set('role', 'super-hacker')
            ->set('password', 'Secret-2026!')
            ->set('password_confirmation', 'autre')
            ->call('save')
            ->assertHasErrors(['role', 'password']);

        $this->assertDatabaseMissing('users', ['email' => 'x@cabinet.ma']);
    }

    public function test_admin_edits_a_user_and_changes_role(): void
    {
        $this->actingAs($this->admin());
        $secretary = $this->secretary();

        Livewire::test(UserIndex::class)
            ->call('openEdit', $secretary->id)
            ->assertSet('role', 'secretaire')
            ->set('role', 'medecin')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($secretary->fresh()->hasRole('medecin'));
        $this->assertFalse($secretary->fresh()->hasRole('secretaire'));
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $admin = $this->admin();
        $doctor = $this->doctor(['email' => 'doc@cabinet.ma']);

        $this->actingAs($admin);
        Livewire::test(UserIndex::class)->call('toggleActive', $doctor->id);
        $this->assertFalse($doctor->fresh()->is_active);

        auth()->logout();

        $this->post(route('login.store'), ['email' => 'doc@cabinet.ma', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_reactivated_user_can_log_in_again(): void
    {
        $doctor = $this->doctor(['email' => 'doc@cabinet.ma', 'is_active' => false]);
        $this->actingAs($this->admin());
        Livewire::test(UserIndex::class)->call('toggleActive', $doctor->id);
        auth()->logout();

        $this->post(route('login.store'), ['email' => 'doc@cabinet.ma', 'password' => 'password'])
            ->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($doctor->fresh());
    }

    public function test_open_session_of_deactivated_user_is_terminated(): void
    {
        $doctor = $this->doctor();
        $this->actingAs($doctor);
        $this->get(route('home'))->assertOk();

        $doctor->update(['is_active' => false]);

        $this->get(route('patients.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_cannot_deactivate_or_delete_themselves(): void
    {
        $admin = $this->admin();
        $this->admin(); // un second admin : la règle « soi-même » s'applique quand même
        $this->actingAs($admin);

        Livewire::test(UserIndex::class)->call('toggleActive', $admin->id);
        Livewire::test(UserIndex::class)->call('delete', $admin->id);
        Livewire::test(UserIndex::class)
            ->call('openEdit', $admin->id)
            ->set('is_active', false)
            ->call('save')
            ->assertHasErrors('is_active');

        $this->assertTrue($admin->fresh()->is_active);
        $this->assertNotNull($admin->fresh());
    }

    public function test_last_active_admin_cannot_be_demoted(): void
    {
        $admin = $this->admin();
        // Le seeder crée aussi admin@cabinet.ma : on ne garde qu'un seul admin actif
        User::role('admin')->whereKeyNot($admin->id)->update(['is_active' => false]);
        $this->actingAs($admin);

        Livewire::test(UserIndex::class)
            ->call('openEdit', $admin->id)
            ->set('role', 'medecin')
            ->call('save')
            ->assertHasErrors('role');

        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }

    public function test_admin_can_demote_another_admin_when_one_remains(): void
    {
        $this->actingAs($this->admin());
        $other = $this->admin();

        Livewire::test(UserIndex::class)
            ->call('openEdit', $other->id)
            ->set('role', 'secretaire')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($other->fresh()->hasRole('secretaire'));
    }

    public function test_user_without_clinical_records_can_be_deleted(): void
    {
        $this->actingAs($this->admin());
        $secretary = $this->secretary();

        Livewire::test(UserIndex::class)->call('delete', $secretary->id);

        $this->assertNull($secretary->fresh());
    }

    public function test_doctor_with_consultations_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin());
        $doctor = $this->doctor();
        $consultation = Consultation::create([
            'patient_id' => $this->makePatient()->id,
            'doctor_id' => $doctor->id,
            'consultation_date' => now(),
        ]);

        Livewire::test(UserIndex::class)->call('delete', $doctor->id);

        $this->assertNotNull($doctor->fresh());
        $this->assertNotNull(Consultation::find($consultation->id));
    }

    public function test_non_admins_cannot_use_user_admin_component(): void
    {
        $target = $this->secretary();

        foreach ([$this->doctor(), $this->secretary()] as $user) {
            $this->actingAs($user);
            Livewire::test(UserIndex::class)->assertForbidden();
        }

        $this->assertNotNull($target->fresh());
    }
}
