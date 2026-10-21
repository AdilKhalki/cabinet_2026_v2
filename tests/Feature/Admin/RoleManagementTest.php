<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\RoleIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
    }

    public function test_admin_edits_role_permissions_and_access_changes(): void
    {
        $this->actingAs($this->admin());
        $doctor = $this->doctor();
        $role = Role::findByName('medecin');

        $permissions = $role->permissions->pluck('name')->reject(fn ($p) => $p === 'view reports')->values()->all();

        Livewire::test(RoleIndex::class)
            ->call('openForm', $role->id)
            ->assertSet('name', 'medecin')
            ->set('selectedPermissions', $permissions)
            ->call('save')
            ->assertHasNoErrors();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->assertFalse($doctor->fresh()->can('view reports'));

        $this->actingAs($doctor->fresh());
        $this->get(route('reports.financial'))->assertForbidden();
    }

    public function test_admin_role_always_keeps_all_permissions(): void
    {
        $this->actingAs($this->admin());
        $role = Role::findByName('admin');

        Livewire::test(RoleIndex::class)
            ->call('openForm', $role->id)
            ->set('selectedPermissions', ['view patients'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(Permission::count(), $role->fresh()->permissions()->count());
    }

    public function test_system_roles_cannot_be_renamed_or_deleted(): void
    {
        $this->actingAs($this->admin());
        $role = Role::findByName('medecin');

        Livewire::test(RoleIndex::class)
            ->call('openForm', $role->id)
            ->set('name', 'docteur')
            ->call('save')
            ->assertHasErrors('name');

        Livewire::test(RoleIndex::class)
            ->set('deletingId', $role->id)
            ->call('delete');

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'medecin']);
    }

    public function test_admin_creates_custom_role_and_rejects_unknown_permissions(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(RoleIndex::class)
            ->call('openForm')
            ->set('name', 'infirmier')
            ->set('selectedPermissions', ['view patients', 'n importe quoi'])
            ->call('save')
            ->assertHasErrors('selectedPermissions.1');

        Livewire::test(RoleIndex::class)
            ->call('openForm')
            ->set('name', 'infirmier')
            ->set('selectedPermissions', ['view patients', 'view appointments'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEqualsCanonicalizing(
            ['view patients', 'view appointments'],
            Role::findByName('infirmier')->permissions->pluck('name')->all(),
        );
    }

    public function test_duplicate_role_name_is_rejected(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(RoleIndex::class)
            ->call('openForm')
            ->set('name', 'secretaire')
            ->call('save')
            ->assertHasErrors('name');
    }

    public function test_custom_role_with_users_cannot_be_deleted_but_empty_one_can(): void
    {
        $this->actingAs($this->admin());
        $used = Role::create(['name' => 'stagiaire']);
        $this->userWithRole('stagiaire');
        $empty = Role::create(['name' => 'vide']);

        Livewire::test(RoleIndex::class)->set('deletingId', $used->id)->call('delete');
        Livewire::test(RoleIndex::class)->call('confirmDelete', $empty->id)->call('delete');

        $this->assertDatabaseHas('roles', ['name' => 'stagiaire']);
        $this->assertDatabaseMissing('roles', ['name' => 'vide']);
    }

    public function test_non_admins_cannot_use_role_component(): void
    {
        foreach ([$this->doctor(), $this->secretary()] as $user) {
            $this->actingAs($user);
            Livewire::test(RoleIndex::class)->assertForbidden();
        }
    }
}
